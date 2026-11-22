<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * /laporan: search approved reports by game ID or WhatsApp number, and let visitors file a new report from the
 * "Buat Laporan" dialog. New reports wait for a super admin (Admin > Laporan) before they are shown.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:60']);
        $query = trim($data['q'] ?? '');
        $approved = Report::approved();

        return view('catalog.reports.index', [
            'query' => $query,
            'reports' => (clone $approved)->search($query)->latest('approved_at')->latest('id')->paginate(12)->withQueryString(),
            'stats' => [
                'total' => (clone $approved)->count(),
                'gameIds' => (clone $approved)->whereNotNull('game_id')->distinct()->count('game_id'),
                'numbers' => (clone $approved)->whereNotNull('wa_number')->distinct()->count('wa_number'),
            ],
        ]);
    }

    public function show(Report $report): View
    {
        abort_unless($report->status === 'approved', 404);

        return view('catalog.reports.show', ['report' => $report]);
    }

    public function store(Request $request): RedirectResponse
    {
        $done = 'Terima kasih! Laporanmu sudah dikirim dan akan dicek admin sebelum ditampilkan.';
        // Hidden "website" field: people never fill it, simple spam bots do. Pretend it worked.
        if (filled($request->input('website'))) {
            return redirect()->route('reports')->with('success', $done);
        }
        $phone = 'regex:/^[+\d][\d\s\-]{7,19}$/';
        $data = $request->validate([
            'reporter_name' => ['required', 'string', 'max:60'],
            'reporter_email' => ['required', 'email', 'max:120'],
            'reporter_whatsapp' => ['required', 'string', 'max:20', $phone],
            'category' => ['required', Rule::in(array_keys(Report::CATEGORIES))],
            'game_id' => ['nullable', 'required_if:category,id_game', 'string', 'max:50'],
            'wa_number' => ['nullable', 'required_if:category,pengguna', 'string', 'max:20', $phone],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'evidence' => ['required', 'array', 'min:1', 'max:'.Report::MAX_EVIDENCE],
            'evidence.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'game_id.required_if' => 'Isi ID game yang bermasalah.',
            'wa_number.required_if' => 'Isi nomor WA pelaku.',
            'wa_number.regex' => 'Nomor WA pelaku tidak valid.',
            'reporter_whatsapp.regex' => 'Nomor WhatsApp kamu tidak valid.',
            'description.min' => 'Ceritakan detail laporan minimal 20 karakter.',
            'evidence.required' => 'Upload minimal 1 gambar bukti.',
            'evidence.max' => 'Maksimal '.Report::MAX_EVIDENCE.' gambar bukti.',
            'evidence.*.max' => 'Setiap gambar bukti maksimal 10 MB.',
        ]);
        $paths = array_map(fn ($file): string => $file->store('reports', 'public'), $request->file('evidence', []));
        Report::create([
            ...$data,
            // A "Pengguna Bermasalah" report is about a person, not a game ID.
            'game_id' => $data['category'] === 'id_game' && filled($data['game_id'] ?? null) ? Report::normaliseGameId($data['game_id']) : null,
            'wa_number' => filled($data['wa_number'] ?? null) ? Report::normaliseWhatsapp($data['wa_number']) : null,
            'reporter_whatsapp' => Report::normaliseWhatsapp($data['reporter_whatsapp']),
            'evidence' => $paths,
        ]);

        return redirect()->route('reports')->with('success', $done);
    }
}
