<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdShare;
use App\Models\AffiliateClick;
use App\Models\AffiliateOrder;
use App\Models\Product;
use App\Models\User;
use App\Services\AdMediaService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\AdminPresenter;
use Inertia\Inertia;

/**
 * Ad Manager: the pictures and clips members share with their affiliate link.
 *
 * Files (uploads, thumbnails, link-preview pictures) are handled by
 * AdMediaService; this controller validates input and keeps the ad row and
 * its files in step. Files live in the public assets/images/ads folder,
 * which a deploy must never overwrite.
 */
class AdController extends Controller
{
    private const IMAGE_MAX_KB  = 8 * 1024;
    private const VIDEO_MAX_KB  = 50 * 1024;
    private const POSTER_MAX_KB = 4 * 1024;

    // Orders that brought money in (the same statuses the affiliate dashboard counts)
    private const PAID_STATUSES = [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED];

    public function __construct(private AdMediaService $media)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $pageTitle = 'Ad Manager';
        $filter    = in_array($request->status, ['live', 'paused'], true) ? $request->status : 'all';

        $ads = Ad::with('product:id,name')
            ->withCount(['shares', 'clicks'])
            ->when($filter === 'live', fn ($q) => $q->where('is_active', true))
            ->when($filter === 'paused', fn ($q) => $q->where('is_active', false))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        // Ads uploaded before previews existed get them the first time an admin looks
        $ads->each(fn (Ad $ad) => $this->media->buildPreviews($ad));

        $salesByAd = $this->salesByAd($ads->pluck('id')->all());

        $counts = [
            'all'    => Ad::count(),
            'live'   => Ad::active()->count(),
            'paused' => Ad::where('is_active', false)->count(),
        ];

        $totals = $this->salesByAd()->reduce(fn ($carry, $row) => [
            'orders'  => $carry['orders'] + $row->orders,
            'revenue' => $carry['revenue'] + $row->revenue,
        ], ['orders' => 0, 'revenue' => 0]);

        $totals['shares'] = AdShare::count();
        $totals['visits'] = AffiliateClick::whereNotNull('ad_id')->count();

        $channels = AdShare::selectRaw('channel, COUNT(*) AS total')
            ->groupBy('channel')
            ->orderByDesc('total')
            ->pluck('total', 'channel');

        $promoters = $this->topPromoters();
        $topChannel = max(1, (int) $channels->max());

        return Inertia::render('Admin/Ads/Index', [
            'ads' => $ads->through(function (Ad $ad) use ($salesByAd) {
                $sales = $salesByAd->get($ad->id);
                return [
                    'id'      => $ad->id,
                    'title'   => (string) $ad->title,
                    'video'   => $ad->isVideo(),
                    'media'   => $ad->mediaUrl(),
                    'thumb'   => $ad->thumbUrl(),
                    'active'  => (bool) $ad->is_active,
                    'order'   => (int) $ad->sort_order,
                    'product' => $ad->product?->name,
                    'shares'  => (int) $ad->shares_count,
                    'visits'  => (int) $ad->clicks_count,
                    'orders'  => (int) ($sales->orders ?? 0),
                    'sales'   => showAmount($sales->revenue ?? 0),
                    'hasSales' => (bool) ($sales->revenue ?? 0),
                    'urls'    => [
                        'edit'   => route('admin.ads.edit', $ad->id),
                        'status' => route('admin.ads.status', $ad->id),
                        'delete' => route('admin.ads.delete', $ad->id),
                    ],
                ];
            }),
            'filter'   => $filter,
            'counts'   => $counts,
            'totals'   => [
                'shares'  => number_format($totals['shares']),
                'visits'  => number_format($totals['visits']),
                'orders'  => (int) $totals['orders'],
                'revenue' => showAmount($totals['revenue']),
            ],
            'channels' => $channels->map(fn ($total, $channel) => [
                'key'     => (string) $channel,
                'label'   => AdShare::CHANNELS[$channel] ?? ucfirst((string) $channel),
                'icon'    => AdShare::CHANNEL_ICONS[$channel] ?? 'las la-share',
                'total'   => (int) $total,
                'percent' => max(3, (int) round($total / $topChannel * 100)),
            ])->values(),
            'promoters' => $promoters->map(fn ($row) => ['user' => AdminPresenter::user($row->user), 'visits' => $row->visits, 'orders' => $row->orders])->values(),
            'urls' => ['create' => route('admin.ads.create')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function create()
    {
        return $this->formPage('New Ad', new Ad(['is_active' => true, 'sort_order' => 0]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);

        $ad = new Ad($data);
        $this->attachUploads($request, $ad);
        $ad->save();

        $notify[] = ['success', 'Ad published. Members can share it from their Ad Center.'];
        return to_route('admin.ads.index')->withNotify($notify);
    }

    public function edit(Ad $ad)
    {
        return $this->formPage('Edit Ad', $ad);
    }

    public function update(Request $request, Ad $ad)
    {
        $data = $this->validated($request, $ad);

        $ad->fill($data);
        $this->attachUploads($request, $ad);
        $ad->save();

        $notify[] = ['success', 'Ad updated.'];
        return to_route('admin.ads.index')->withNotify($notify);
    }

    /** `state=live|paused`; a repeat from a stale tab changes nothing. Without it (old links) it flips. */
    public function status(Request $request, Ad $ad)
    {
        $state = $request->input('state');
        $want  = $state === 'live' ? true : ($state === 'paused' ? false : !$ad->is_active);
        if ((bool) $ad->is_active === $want) {
            $notify[] = ['info', $want ? 'The ad is already live.' : 'The ad is already paused.'];
            return back()->withNotify($notify);
        }
        $ad->is_active = $want;
        $ad->save();

        $notify[] = ['success', $ad->is_active ? 'Ad is live again.' : 'Ad paused. Links already shared still open the shop.'];
        return back()->withNotify($notify);
    }

    public function destroy(Ad $ad)
    {
        $files = array_filter([$ad->media, $ad->poster]);

        // Shares go with the ad; clicks keep their history with ad_id set to null
        $ad->delete();

        foreach ($files as $file) {
            $this->media->remove($file);
        }

        $notify[] = ['success', 'Ad deleted. Links already shared now open the shop.'];
        return to_route('admin.ads.index')->withNotify($notify);
    }

    /** New / Edit Ad (Pages/Admin/Ads/Form.tsx). */
    private function formPage(string $pageTitle, Ad $ad)
    {
        // What PHP on this server will actually accept, so the form can warn before uploading
        $serverMaxKb = (int) floor(min(
            $this->iniBytes(ini_get('upload_max_filesize')),
            $this->iniBytes(ini_get('post_max_size'))
        ) / 1024);

        return Inertia::render('Admin/Ads/Form', [
            'title' => $pageTitle,
            'ad'    => [
                'id'         => $ad->exists ? $ad->id : null,
                'title'      => (string) $ad->title,
                'caption'    => (string) $ad->caption,
                'product_id' => $ad->product_id ? (string) $ad->product_id : '',
                'sort_order' => (string) ($ad->sort_order ?? 0),
                'is_active'  => (bool) $ad->is_active,
                'video'      => $ad->exists && $ad->isVideo(),
                'media'      => $ad->exists && $ad->media ? $ad->mediaUrl() : null,
                'poster'     => $ad->poster ? $ad->posterUrl() : null,
                'preview'    => $ad->exists && $ad->pictureSource() ? $ad->previewImageUrl() : null,
            ],
            'products'   => Product::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'name' => (string) $p->name]),
            'limits'     => [
                'image'  => min(self::IMAGE_MAX_KB, $serverMaxKb),
                'video'  => min(self::VIDEO_MAX_KB, $serverMaxKb),
                'poster' => min(self::POSTER_MAX_KB, $serverMaxKb),
                'server' => $serverMaxKb,
            ],
            'extensions' => ['image' => Ad::IMAGE_EXTENSIONS, 'video' => Ad::VIDEO_EXTENSIONS],
            'shopHost'   => parse_url(route('shop.index'), PHP_URL_HOST),
            'urls'       => [
                'save'  => $ad->exists ? route('admin.ads.update', $ad->id) : route('admin.ads.store'),
                'index' => route('admin.ads.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function validated(Request $request, ?Ad $ad): array
    {
        $mediaExtensions = implode(',', array_merge(Ad::IMAGE_EXTENSIONS, Ad::VIDEO_EXTENSIONS));
        $imageExtensions = implode(',', Ad::IMAGE_EXTENSIONS);

        $request->validate([
            'title'      => 'required|string|max:120',
            'caption'    => 'nullable|string|max:1000',
            'product_id' => 'nullable|integer|exists:products,id',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            // extensions: the name we keep; mimetypes: what the bytes really are
            'media'      => [$ad ? 'nullable' : 'required', 'file', 'extensions:' . $mediaExtensions,
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime', 'max:' . self::VIDEO_MAX_KB],
            'poster'     => ['nullable', 'file', 'extensions:' . $imageExtensions,
                'mimetypes:image/jpeg,image/png,image/webp', 'max:' . self::POSTER_MAX_KB],
        ], [
            'media.required'  => 'Choose a picture or a video clip.',
            'media.uploaded'  => 'The file is larger than this server accepts. Raise upload_max_filesize and post_max_size in cPanel.',
            'media.max'       => 'Video clips can be up to 50 MB.',
            'poster.max'      => 'The cover picture can be up to 4 MB.',
        ]);

        $media = $request->file('media');
        if ($media && $this->typeOf($media) === Ad::TYPE_IMAGE && $media->getSize() > self::IMAGE_MAX_KB * 1024) {
            throw ValidationException::withMessages(['media' => 'Pictures can be up to 8 MB.']);
        }

        return [
            'title'      => trim($request->title),
            'caption'    => trim((string) $request->caption) ?: null,
            'product_id' => $request->product_id ?: null,
            'sort_order' => (int) $request->sort_order,
            'is_active'  => $request->boolean('is_active'),
        ];
    }

    /**
     * Stores new media / poster files on the ad, builds its previews and removes
     * the files they replace. New files are written before old ones are deleted,
     * so a failed upload never leaves an ad pointing at nothing.
     */
    private function attachUploads(Request $request, Ad $ad): void
    {
        $replaced = [];

        if ($media = $request->file('media')) {
            $type = $this->typeOf($media);

            $replaced[]     = $ad->media;
            $ad->media      = $type === Ad::TYPE_IMAGE ? $this->storeImage($media, 'media') : $this->media->storeVideo($media);
            $ad->media_type = $type;
        }

        if ($ad->media_type === Ad::TYPE_IMAGE) {
            // A picture is its own preview; a cover left from an earlier video is dead weight
            $replaced[] = $ad->poster;
            $ad->poster = null;
        } elseif ($poster = $request->file('poster')) {
            $replaced[] = $ad->poster;
            $ad->poster = $this->storeImage($poster, 'poster');
        } elseif ($request->boolean('remove_poster')) {
            $replaced[] = $ad->poster;
            $ad->poster = null;
        }

        // New picture → new previews; an old ad saved again gets any it is missing
        $this->media->buildPreviews($ad);

        foreach (array_filter($replaced) as $old) {
            if ($old !== $ad->media && $old !== $ad->poster) {
                $this->media->remove($old);
            }
        }
    }

    private function typeOf(UploadedFile $file): string
    {
        return in_array(strtolower($file->getClientOriginalExtension()), Ad::IMAGE_EXTENSIONS, true)
            ? Ad::TYPE_IMAGE
            : Ad::TYPE_VIDEO;
    }

    private function storeImage(UploadedFile $file, string $field): string
    {
        try {
            return $this->media->storeImage($file);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([$field => 'This picture could not be processed. Try saving it again as a JPG or PNG.']);
        }
    }

    /**
     * Paid orders that came in through each ad: order → the click it was
     * attributed to → the ad that click landed on.
     */
    private function salesByAd(?array $adIds = null)
    {
        return AffiliateOrder::query()
            ->join('affiliate_clicks', 'affiliate_clicks.id', '=', 'affiliate_orders.affiliate_click_id')
            ->whereNotNull('affiliate_clicks.ad_id')
            ->when($adIds !== null, fn ($q) => $q->whereIn('affiliate_clicks.ad_id', $adIds))
            ->whereIn('affiliate_orders.status', self::PAID_STATUSES)
            ->groupBy('affiliate_clicks.ad_id')
            ->get([
                'affiliate_clicks.ad_id',
                DB::raw('COUNT(*) AS orders'),
                DB::raw('SUM(affiliate_orders.total_amount) AS revenue'),
            ])
            ->keyBy('ad_id');
    }

    /** Members whose ad links bring the most visitors, with the orders those visits became. */
    private function topPromoters()
    {
        $visits = AffiliateClick::whereNotNull('ad_id')
            ->selectRaw('affiliate_user_id, COUNT(*) AS visits')
            ->groupBy('affiliate_user_id')
            ->orderByDesc('visits')
            ->limit(5)
            ->pluck('visits', 'affiliate_user_id');

        if ($visits->isEmpty()) {
            return collect();
        }

        $orders = AffiliateOrder::query()
            ->join('affiliate_clicks', 'affiliate_clicks.id', '=', 'affiliate_orders.affiliate_click_id')
            ->whereNotNull('affiliate_clicks.ad_id')
            ->whereIn('affiliate_orders.affiliate_user_id', $visits->keys())
            ->whereIn('affiliate_orders.status', self::PAID_STATUSES)
            ->groupBy('affiliate_orders.affiliate_user_id')
            ->selectRaw('affiliate_orders.affiliate_user_id, COUNT(*) AS orders')
            ->pluck('orders', 'affiliate_user_id');

        $users = User::whereIn('id', $visits->keys())->get(['id', 'firstname', 'lastname', 'username'])->keyBy('id');

        return $visits->map(fn ($count, $userId) => (object) [
            'user'   => $users->get($userId),
            'visits' => (int) $count,
            'orders' => (int) ($orders[$userId] ?? 0),
        ])->filter(fn ($row) => $row->user)->values();
    }

    private function iniBytes($value): int
    {
        $value = trim((string) $value);
        $unit  = strtolower(substr($value, -1));
        $bytes = (int) $value;

        return match ($unit) {
            'g' => $bytes * 1024 ** 3,
            'm' => $bytes * 1024 ** 2,
            'k' => $bytes * 1024,
            default => $bytes,
        } ?: PHP_INT_MAX;
    }
}
