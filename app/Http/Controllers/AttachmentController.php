<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        abort_unless($this->userCan($request, 'shipments.manage'), 403);

        abort_if($attachment->attachable_type !== Shipment::class, 404);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Bukti foto pengiriman dihapus.');
    }
}
