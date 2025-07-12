<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDetail;

class UserDetailsController extends Controller
{
    public function index(Request $request)
    {
        $query = UserDetail::query();

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('country', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('registration_date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        return response()->json(
            $query->paginate(5)->withQueryString()
        );
    }

    public function update(Request $request, $id)
    {
        $user = UserDetail::findOrFail($id);
        $user->update($request->only(['name', 'email', 'country', 'status']));
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = UserDetail::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
