<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function current()
    {
        $shift = Shift::whereNull('closed_at')->orderByDesc('id')->first();
        return response()->json(['shift' => $shift]);
    }

    public function latest()
    {
        $shift = Shift::orderByDesc('opened_at')->orderByDesc('id')->first();
        return response()->json(['shift' => $shift]);
    }

    public function previous(Shift $shift)
    {
        $prev = Shift::where(function ($q) use ($shift) {
                $q->where('opened_at', '<', $shift->opened_at)
                  ->orWhere(function ($q2) use ($shift) {
                      $q2->whereNull('opened_at')->where('id', '<', $shift->id);
                  });
            })
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->first();
        return response()->json(['shift' => $prev]);
    }

    public function next(Shift $shift)
    {
        $next = Shift::where(function ($q) use ($shift) {
                $q->where('opened_at', '>', $shift->opened_at)
                  ->orWhere(function ($q2) use ($shift) {
                      $q2->whereNull('opened_at')->where('id', '>', $shift->id);
                  });
            })
            ->orderBy('opened_at')
            ->orderBy('id')
            ->first();
        return response()->json(['shift' => $next]);
    }

    public function open(Request $request)
    {
        $validated = $request->validate([
            'opening_cash' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $existing = Shift::whereNull('closed_at')->first();
        if ($existing) {
            return response()->json(['message' => 'A shift is already open.', 'shift' => $existing], 400);
        }

        $shift = Shift::create([
            'opened_by' => Auth::id(),
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Shift opened', 'shift' => $shift], 201);
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'closing_cash' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shift = Shift::whereNull('closed_at')->orderByDesc('id')->first();
        if (!$shift) {
            return response()->json(['message' => 'No open shift to close.'], 400);
        }

        $shift->closed_at = now();
        $shift->closed_by = Auth::id();
        if (isset($validated['closing_cash'])) {
            $shift->closing_cash = $validated['closing_cash'];
        }
        if (isset($validated['notes'])) {
            $shift->notes = trim(($shift->notes ? $shift->notes."\n" : '') . $validated['notes']);
        }
        $shift->save();

        return response()->json(['message' => 'Shift closed', 'shift' => $shift]);
    }
}


