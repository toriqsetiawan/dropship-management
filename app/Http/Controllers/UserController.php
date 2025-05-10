<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('pages.user.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();

        $distributors = User::whereHas('role', function($q) {
            $q->where('name', 'distributor');
        })->get();

        return view('pages.user.create', compact('roles', 'distributors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
            'photo' => ['nullable', 'image', 'max:1024'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
        }

        $validated['password'] = Hash::make($validated['password']);

        if ($request->parent_id) {
            $validated['current_team_id'] = $request->parent_id;
        }

        $user = User::create($validated);

        $role = Role::where('name', $validated['role'])->first();
        $user->role()->associate($role);
        $user->save();

        return redirect()->route('users.index')
            ->with('success', __('user.messages.created'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $distributors = User::whereHas('role', function($q) {
            $q->where('name', 'distributor');
        })->get();
        return view('pages.user.edit', compact('user', 'roles', 'distributors'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
            'photo' => ['nullable', 'image', 'max:1024'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile-photos', 'public');
            $validated['profile_photo_path'] = $path;
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->parent_id) {
            $validated['current_team_id'] = $request->parent_id;
        }

        $user->update($validated);

        $role = Role::where('name', $validated['role'])->first();
        $user->role()->associate($role);
        $user->save();

        return redirect()->route('users.index')
            ->with('success', __('user.messages.updated'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', __('user.messages.deleted'));
    }
}
