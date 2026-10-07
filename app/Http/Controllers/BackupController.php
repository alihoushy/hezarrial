<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Models\Backup;
use App\Services\Audit\AuditLogService;
use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('backups/index', [
            'backups' => Backup::forUser(auth()->user())->latest('created_at')->get()->map(Present::backup(...)),
        ]);
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

    public function restore(Request $request, BackupService $backups, AuditLogService $audit): RedirectResponse
    {
        $request->validate([
            'password' => ['required'],
            'backup' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'رمز عبور درست نیست.']);
        }

        $payload = json_decode($request->file('backup')->get(), true, flags: JSON_THROW_ON_ERROR);
        $restored = $backups->restore($request->user(), $payload);

        $audit->record('backup.restored', null, [], ['schema_version' => 1, 'restored' => $restored]);

        return back()->with('status', 'بازیابی پشتیبان با موفقیت انجام شد.');
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);
        Storage::disk('local')->delete($backup->file_path);
        $backup->delete();

        return back()->with('status', 'پشتیبان حذف شد.');
    }
}
