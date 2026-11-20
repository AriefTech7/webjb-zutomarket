<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin > Laporan (super admin only): read new reports with the reporter's contact, then show, reject or delete them.
 */
class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(array_keys(Report::STATUSES))], 'q' => 'nullable|string|max:60']);
        $status = $data['status'] ?? 'pending';

        return view('admin.reports', [
            'status' => $status,
            'query' => $data['q'] ?? '',
            'reports' => Report::where('status', $status)->search($data['q'] ?? '')->latest()->paginate(15)->withQueryString(),
            'counts' => Report::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Report::STATUSES))]]);
        $report->forceFill([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'approved' ? ($report->approved_at ?? now()) : null,
        ])->save();

        return back()->with('success', ['approved' => 'Laporan ditampilkan di halaman Laporan.', 'rejected' => 'Laporan ditolak.', 'pending' => 'Laporan dikembalikan ke antrean.'][$data['status']]);
    }

    public function destroy(Report $report): RedirectResponse
    {
        Storage::disk('public')->delete($report->evidence ?? []);
        $report->delete();

        return back()->with('success', 'Laporan dan foto buktinya dihapus.');
    }
}
