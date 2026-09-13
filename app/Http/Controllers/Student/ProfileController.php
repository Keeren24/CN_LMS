<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The signed-in student's own profile: name/phone and password.
 *
 * Ported from CN_ADMIN's role-agnostic ProfileController, trimmed to what a
 * student account can actually do — CN_LMS has only one role, so the
 * tutor/admin branches, avatar upload, and staff-activity audit trail (which
 * need a users.avatar_path column and an account_audits table that don't
 * exist in the shared database yet) are left out.
 */
class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('student.profile', [
            'user' => $user,
            'student' => $user->student_id ? Student::with('class_info')->find($user->student_id) : null,
        ]);
    }

    public function updateDetails(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone_no')->ignore($user->id)],
        ], [
            'phone_no.unique' => 'That phone number is already on another account.',
        ]);

        $user->fill([
            'name' => $data['name'],
            'phone_no' => $data['phone_no'] ?? null,
        ])->save();

        // A student's name/phone are stored on `users` and again on `student`,
        // so both have to be written or the two drift apart.
        if ($user->student_id) {
            Student::where('id', $user->student_id)->update([
                'name' => $data['name'],
                'phone_no' => $data['phone_no'] ?? null,
            ]);
        }

        return back()->with('success', 'Your details have been saved.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        Auth::user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('success', 'Your password has been changed.');
    }
}
