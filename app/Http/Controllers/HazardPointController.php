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

        // 1. Удаляем записи о проезде для точек, от которых мы уехали дальше 5000 метров
        $farHazardIds = HazardPoint::query()
            ->whereIn('id', function ($query) use ($userId) {
                $query->select('hazard_point_id')
                    ->from('user_passed_hazards')
                    ->where('user_id', $userId);
            })
            ->whereRaw("{$haversine} > 5000", [$lat, $lng, $lat])
            ->pluck('id');

        if ($farHazardIds->isNotEmpty()) {
            DB::table('user_passed_hazards')
                ->where('user_id', $userId)
                ->whereIn('hazard_point_id', $farHazardIds)
                ->delete();
        }

        // 2. Берем оставшиеся ID проеханных точек (которые ближе 5000 м)
        $passedIds = DB::table('user_passed_hazards')
            ->where('user_id', $userId)
            ->pluck('hazard_point_id');

        // 3. Ищем одобренные точки в опасном радиусе, исключая уже проеханные
        $hazards = HazardPoint::query()
            ->where('status', 'approved')
            ->whereNotIn('id', $passedIds)
            ->select('*')
            ->selectRaw("ROUND({$haversine}, 1) AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} <= danger_radius", [$lat, $lng, $lat])
            ->orderBy('distance')
            ->get();

        // 4. Если до точки <= 5 метров — записываем её как проеханную и убираем из выдачи
        $activeHazards = $hazards->reject(function ($hazard) use ($userId) {
            if ($hazard->distance <= 5) {
                DB::table('user_passed_hazards')->insertOrIgnore([
                    'user_id' => $userId,
                    'hazard_point_id' => $hazard->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return true;
            }
            return false;
        })->values();

        $nearest = $activeHazards->first();

        return response()->json([
            'danger' => $nearest !== null,
            'nearest_hazard' => $nearest,
            'distance_to_nearest' => $nearest ? (float) $nearest->distance : null,
            'hazards' => $activeHazards,
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
