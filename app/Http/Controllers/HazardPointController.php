<?php

namespace App\Http\Controllers;

use App\Models\HazardPoint;
use Illuminate\Http\Request;

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

        $haversine = "(6371000 * acos(
            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(latitude))
        ))";

        $nearbyHazards = HazardPoint::query()
            ->where('status', 'approved')
            ->select('*')
            ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} <= danger_radius", [$lat, $lng, $lat])
            ->orderBy('distance')
            ->get();

        return response()->json([
            'danger' => $nearbyHazards->isNotEmpty(),
            'hazards' => $nearbyHazards,
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
