<?php

namespace App\Http\Controllers;

use App\Models\Ov500\RatedCdr;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // tenancy: admin sees all; reseller/customer only their subtree
        $scope = $user->isAdmin() ? null : $user->accessibleAccountIds();

        $from = $request->query('from');
        $to   = $request->query('to');
        $acct = trim((string) $request->query('account', ''));
        $dir  = $request->query('direction', '');
        $dest = trim((string) $request->query('dest', ''));

        $base = RatedCdr::query()
            ->when($scope !== null, fn ($q) => $q->whereIn('account_id', $scope ?: ['__none__']))
            ->when($acct !== '', fn ($q) => $q->where('account_id', $acct))
            ->when(in_array($dir, ['outbound', 'inbound'], true), fn ($q) => $q->where('direction', $dir))
            ->when($dest !== '', fn ($q) => $q->where('destination_number', 'like', "%{$dest}%"))
            ->when($from, fn ($q) => $q->whereDate('rated_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('rated_at', '<=', $to));

        // summary over the filtered set (clone before pagination)
        $summary = [
            'count'   => (clone $base)->count(),
            'cost'    => (clone $base)->sum('cost'),
            'seconds' => (clone $base)->sum('billed_seconds'),
        ];

        $cdrs = $base->orderByDesc('rated_at')->paginate(50)->withQueryString();

        return view('reports.cdrs', compact('cdrs', 'summary', 'from', 'to', 'acct', 'dir', 'dest'));
    }
}
