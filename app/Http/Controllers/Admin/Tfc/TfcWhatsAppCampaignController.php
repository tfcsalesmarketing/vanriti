<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppCampaignJob;
use App\Models\Tfc\TfcInquiry;
use App\Models\Tfc\TfcGrievanceTicket;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcWhatsAppCampaignController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    /**
     * Display TFC Broadcast Campaigns dashboard.
     */
    public function index(): View
    {
        $campaigns = WhatsAppCampaign::with('creator')
            ->where('channel', 'tfc')
            ->latest()
            ->paginate(15);

        $totalCampaigns = WhatsAppCampaign::where('channel', 'tfc')->count();
        $totalSent = (int) WhatsAppCampaign::where('channel', 'tfc')->sum('sent_count');
        $totalDelivered = (int) WhatsAppCampaign::where('channel', 'tfc')->sum('delivered_count');
        $totalRead = (int) WhatsAppCampaign::where('channel', 'tfc')->sum('read_count');

        $overallDeliveryRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0;
        $overallReadRate = $totalDelivered > 0 ? round(($totalRead / $totalDelivered) * 100, 1) : 0;

        return view('admin.tfc.whatsapp.campaigns.index', compact(
            'campaigns',
            'totalCampaigns',
            'totalSent',
            'overallDeliveryRate',
            'overallReadRate'
        ));
    }

    /**
     * Show the TFC campaign creation wizard.
     */
    public function create(): View
    {
        // Direct-Selling specific audience counts
        $estimates = [
            'tfc_inquiries_all' => TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->count(),
            'tfc_inquiries_approved' => TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->where('status', 'contacted')->count(),
            'tfc_inquiries_pending' => TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->where('status', 'new')->count(),
            'tfc_grievance_consumers' => TfcGrievanceTicket::whereNotNull('phone')->where('phone', '!=', '')->distinct('phone')->count('phone'),
            'registered_customers' => User::whereNotNull('phone')->where('phone', '!=', '')->count(),
        ];

        $approvedTemplates = WhatsAppTemplate::where('channel', 'tfc')->where('status', 'APPROVED')->orderBy('name')->get();

        return view('admin.tfc.whatsapp.campaigns.create', compact('estimates', 'approvedTemplates'));
    }

    /**
     * Store and dispatch a new TFC WhatsApp Broadcast Campaign.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_name' => ['required', 'string', 'max:120'],
            'template_lang' => ['required', 'string', 'max:10'],
            'target_segment' => ['required', 'string'],
            'custom_numbers' => ['nullable', 'string'],
            'parameters' => ['nullable', 'array'],
            'header_image_url' => ['nullable', 'url:https', 'max:500'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $recipients = $this->resolveRecipients($validated['target_segment'], $validated['custom_numbers'] ?? null);

        if (empty($recipients)) {
            return back()->withInput()->with('error', 'Selected audience segment yielded zero valid phone recipients. Please select another segment or enter custom numbers.');
        }

        $campaign = WhatsAppCampaign::create([
            'name' => $validated['name'],
            'channel' => 'tfc',
            'template_name' => $validated['template_name'],
            'template_lang' => $validated['template_lang'],
            'target_segment' => $validated['target_segment'],
            'parameters' => $validated['parameters'] ?? [],
            'header_image_url' => $validated['header_image_url'] ?? null,
            'status' => ! empty($validated['scheduled_at']) ? 'scheduled' : 'draft',
            'total_recipients' => count($recipients),
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'created_by' => auth('admin')->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Bulk insert campaign recipients
        $recipientData = [];
        $now = now();
        foreach ($recipients as $item) {
            $recipientData[] = [
                'whatsapp_campaign_id' => $campaign->id,
                'phone' => $item['phone'],
                'customer_name' => $item['name'] ?? null,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($recipientData, 500) as $chunk) {
            WhatsAppCampaignRecipient::insert($chunk);
        }

        // Dispatch broadcast processing job if not scheduled for future
        if (empty($validated['scheduled_at'])) {
            ProcessWhatsAppCampaignJob::dispatch($campaign);
            $msg = "TFC WhatsApp Campaign '{$campaign->name}' launched successfully to " . count($recipients) . ' recipients!';
        } else {
            $msg = "TFC WhatsApp Campaign '{$campaign->name}' scheduled successfully for {$campaign->scheduled_at->format('M d, Y h:i A')}!";
        }

        return redirect()->route('admin.tfc.whatsapp.campaigns.show', $campaign)->with('success', $msg);
    }

    /**
     * View detailed performance analytics of a campaign.
     */
    public function show(WhatsAppCampaign $campaign): View
    {
        $campaign->load('creator');

        $recipients = $campaign->recipients()
            ->latest()
            ->paginate(30);

        $deliveryRate = $campaign->sent_count > 0 ? round(($campaign->delivered_count / $campaign->sent_count) * 100, 1) : 0;
        $readRate = $campaign->delivered_count > 0 ? round(($campaign->read_count / $campaign->delivered_count) * 100, 1) : 0;

        return view('admin.tfc.whatsapp.campaigns.show', compact('campaign', 'recipients', 'deliveryRate', 'readRate'));
    }

    /**
     * Cancel a scheduled or pending campaign.
     */
    public function cancel(WhatsAppCampaign $campaign): RedirectResponse
    {
        if (in_array($campaign->status, ['completed', 'failed'], true)) {
            return back()->with('error', 'Campaign cannot be cancelled in its current state.');
        }

        $campaign->update(['status' => 'failed', 'notes' => ($campaign->notes ? $campaign->notes . ' | ' : '') . 'Cancelled by admin']);

        return back()->with('success', 'Campaign cancelled successfully.');
    }

    /**
     * Resolve target audience recipients array.
     *
     * @return array<int, array{phone: string, name: ?string}>
     */
    protected function resolveRecipients(string $segment, ?string $customNumbers = null): array
    {
        $results = [];
        $seen = [];

        if ($segment === 'tfc_inquiries_all') {
            $inquiries = TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->get(['phone', 'name']);
            foreach ($inquiries as $inq) {
                $p = $this->formatPhone($inq->phone);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => $inq->name];
                }
            }
        } elseif ($segment === 'tfc_inquiries_approved') {
            $inquiries = TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->where('status', 'contacted')->get(['phone', 'name']);
            foreach ($inquiries as $inq) {
                $p = $this->formatPhone($inq->phone);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => $inq->name];
                }
            }
        } elseif ($segment === 'tfc_inquiries_pending') {
            $inquiries = TfcInquiry::whereNotNull('phone')->where('phone', '!=', '')->where('status', 'new')->get(['phone', 'name']);
            foreach ($inquiries as $inq) {
                $p = $this->formatPhone($inq->phone);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => $inq->name];
                }
            }
        } elseif ($segment === 'tfc_grievance_consumers') {
            $tickets = TfcGrievanceTicket::whereNotNull('phone')->where('phone', '!=', '')->get(['phone', 'consumer_name']);
            foreach ($tickets as $t) {
                $p = $this->formatPhone($t->phone);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => $t->consumer_name];
                }
            }
        } elseif ($segment === 'registered_customers') {
            $users = User::whereNotNull('phone')->where('phone', '!=', '')->get(['phone', 'name']);
            foreach ($users as $u) {
                $p = $this->formatPhone($u->phone);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => $u->name];
                }
            }
        }

        // Custom comma or newline delimited numbers
        if (! empty($customNumbers)) {
            $lines = preg_split('/[\r\n,]+/', $customNumbers);
            foreach ($lines as $line) {
                $clean = trim($line);
                if (empty($clean)) {
                    continue;
                }
                $p = $this->formatPhone($clean);
                if ($p && ! isset($seen[$p])) {
                    $seen[$p] = true;
                    $results[] = ['phone' => $p, 'name' => null];
                }
            }
        }

        return $results;
    }

    protected function formatPhone(string $raw): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($clean) === 10) {
            $clean = '91' . $clean;
        }
        return strlen($clean) >= 10 ? $clean : null;
    }
}
