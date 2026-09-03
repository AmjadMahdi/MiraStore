<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SheinCart;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SheinCartItemsExportController extends Controller
{
    public function __invoke(Request $request, SheinCart $cart): StreamedResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter();

        abort_if($ids->isEmpty(), 404);

        $items = $cart->items()->whereIn('id', $ids)->orderByDesc('item_date')->get();

        abort_if($items->isEmpty(), 404);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        $sheet->fromArray(['الوصف', 'الرابط', 'الكمية', 'رقم واتساب العميل', 'التاريخ والوقت'], null, 'A1');

        $row = 2;
        foreach ($items as $item) {
            $sheet->fromArray([
                $item->name,
                $item->link,
                $item->quantity,
                $item->customer_phone,
                $item->item_date->format('Y-m-d H:i'),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$cart->cart_number.'.xlsx"',
        ]);
    }
}
