<?php

namespace App\Http\Controllers;

use App\Models\HazardPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HazardPointController extends Controller
{
    // Все подтвержденные точки для карты
    public function index()
    {
        return response()->json(
            HazardPoint::where('status', 'approved')->latest()->get()
        );
    }

    // Проверка приближения к опасным объектам (Радар)
    public function checkProximity(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = $data['lat'];
        $lng = $data['lng'];
        $userId = $request->user()->id;

        $haversine = "(6371000 * acos(
            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(latitude))
        ))";

        // 1. Получаем все одобренные точки с расчетом дистанции до каждой
        $allHazards = HazardPoint::query()
            ->where('status', 'approved')
            ->select('*')
            ->selectRaw("ROUND({$haversine}, 1) AS distance", [$lat, $lng, $lat])
            ->get()
            ->keyBy('id');

        // 2. Загружаем историю взаимодействий этого пользователя с точками
        $userRecords = DB::table('user_passed_hazards')
            ->where('user_id', $userId)
            ->get()
            ->keyBy('hazard_point_id');

        $activeHazards = collect();

        foreach ($allHazards as $hazardId => $hazard) {
            $dist = (float) $hazard->distance;
            $record = $userRecords->get($hazardId);

            // Если уехали дальше 5000 метров — сбрасываем статус проезда
            if ($dist > 5000) {
                if ($record) {
                    DB::table('user_passed_hazards')->where('id', $record->id)->delete();
                }
                continue;
            }

            // Если мы внутри опасного радиуса
            if ($dist <= $hazard->danger_radius) {
                if (! $record) {
                    // Впервые вошли в зону опасности — записываем в базу и показываем тревогу
                    DB::table('user_passed_hazards')->insert([
                        'user_id' => $userId,
                        'hazard_point_id' => $hazardId,
                        'last_distance' => $dist,
                        'is_passed' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $activeHazards->push($hazard);
                } else {
                    // Если точка уже помечена как проеханная — пропускаем её!
                    if ($record->is_passed) {
                        continue;
                    }

                    // Проверяем: дистанция начала расти (с запасом 5 метров от погрешности GPS)?
                    // Значит, мы проехали точку мимо и теперь удаляемся от неё!
                    if ($dist > $record->last_distance + 5) {
                        DB::table('user_passed_hazards')
                            ->where('id', $record->id)
                            ->update([
                                'is_passed' => true,
                                'last_distance' => $dist,
                                'updated_at' => now(),
                            ]);
                        continue; // Больше не показываем как опасность
                    }

                    // Если всё ещё приближаемся — обновляем минимальную дистанцию и продолжаем предупреждать
                    if ($dist < $record->last_distance) {
                        DB::table('user_passed_hazards')
                            ->where('id', $record->id)
                            ->update([
                                'last_distance' => $dist,
                                'updated_at' => now(),
                            ]);
                    }

                    $activeHazards->push($hazard);
                }
            }
        }
        // Сортируем актуальные опасности от ближайшей к дальней
        $sortedHazards = $activeHazards->sortBy('distance')->values();
        $nearest = $sortedHazards->first();

        return response()->json([
            'danger' => $nearest !== null,
            'nearest_hazard' => $nearest,
            'distance_to_nearest' => $nearest ? (float) $nearest->distance : null,
            'hazards' => $sortedHazards,
        ]);
    }

    // Создание новой точки (работником или модератором)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'danger_radius' => 'nullable|integer|min:10|max:5000',
            'photo' => 'nullable|image|max:5120', // до 5 МБ
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('hazards', 'public');
        }

        $user = $request->user();
        $isStaff = in_array($user->role, ['moderator', 'admin']);

        $hazard = HazardPoint::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'danger_radius' => $data['danger_radius'] ?? 100,
            'photo_path' => $photoPath,
            'status' => $isStaff ? 'approved' : 'pending',
            'created_by' => $user->id,
            'moderated_by' => $isStaff ? $user->id : null,
        ]);

        return response()->json($hazard, 201);
    }

    // Список заявок на проверку (для модератора и админа)
    public function pending()
    {
        return response()->json(
            HazardPoint::with('creator:id,name,email')
                ->where('status', 'pending')
                ->latest()
                ->get()
        );
    }

    // Редактирование и модерация точки
    public function update(Request $request, HazardPoint $hazardPoint)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'danger_radius' => 'sometimes|integer|min:10|max:5000',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);

        if (isset($data['status'])) {
            $data['moderated_by'] = $request->user()->id;
        }

        $hazardPoint->update($data);

        return response()->json($hazardPoint);
    }

    // Удаление точки
    public function destroy(HazardPoint $hazardPoint)
    {
        $hazardPoint->delete();
        return response()->json(['message' => 'Точка удалена']);
    }
}
