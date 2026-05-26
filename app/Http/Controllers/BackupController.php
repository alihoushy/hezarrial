<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\Audit\AuditLogService;
use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('backups.index', ['backups' => Backup::forUser(auth()->user())->latest('created_at')->get()]);
    }

    public function store(BackupService $backups): RedirectResponse
    {
        $backups->create(auth()->user());

        return back()->with('status', 'پشتیبان ساخته شد.');
    }

    public function download(Request $request, Backup $backup): StreamedResponse
    {
        $this->authorize('view', $backup);
        $request->validate(['password' => ['required', 'current_password']]);

        return Storage::disk('local')->download($backup->file_path, $backup->file_name);
    }

    public function restore(Request $request, AuditLogService $audit): RedirectResponse
    {
        $request->validate([
            'password' => ['required'],
            'backup' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'رمز عبور درست نیست.']);
        }

        $payload = json_decode($request->file('backup')->get(), true);
        if (($payload['schema_version'] ?? null) !== 1) {
            return back()->withErrors(['backup' => 'نسخه فایل پشتیبان پشتیبانی نمی‌شود.']);
        }

        $audit->record('backup.restore.requested', null, [], ['schema_version' => 1]);

        return back()->with('status', 'فایل پشتیبان معتبر است. بازیابی کامل داده‌ها در گام تایید نهایی انجام می‌شود.');
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);
        Storage::disk('local')->delete($backup->file_path);
        $backup->delete();

        return back()->with('status', 'پشتیبان حذف شد.');
    }
}
