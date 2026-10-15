<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Rebate;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        abort_if(! in_array($attachment->attachable_type, [Shipment::class, Invoice::class, Rebate::class], true), 404);

        $permission = match ($attachment->attachable_type) {
            Invoice::class => 'invoices.manage',
            Rebate::class => 'rebates.pay',
            default => 'shipments.manage',
        };
        abort_unless($this->userCan($request, $permission), 403);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', match ($attachment->attachable_type) {
            Invoice::class => 'Bukti foto pembayaran dihapus.',
            Rebate::class => 'Bukti foto pembayaran rebate dihapus.',
            default => 'Bukti foto pengiriman dihapus.',
        });
    }
}
