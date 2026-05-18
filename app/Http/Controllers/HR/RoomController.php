<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreRoomRequest;
use App\Models\HR\Room;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::orderBy('active', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('HR/Rooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $this->authorize('create', Room::class);

        Room::create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return redirect()
            ->route('hr.rooms.index')
            ->with('success', __('app.hr_room_created'));
    }

    public function update(StoreRoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $room->update($request->validated());

        return redirect()
            ->route('hr.rooms.index')
            ->with('success', __('app.hr_room_updated'));
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        // Block delete if room is used in any future event
        $hasFutureEvents = $room->events()
            ->where('ends_at', '>', now())
            ->exists();

        if ($hasFutureEvents) {
            return back()->with('error', __('app.hr_room_has_future_events'));
        }

        $room->delete();

        return redirect()
            ->route('hr.rooms.index')
            ->with('success', __('app.hr_room_deleted'));
    }
}
