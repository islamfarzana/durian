<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class subscribersExport implements FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
{

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $subscriberData = \App\Models\NewsletterSubscriber::select('id', 'email', 'created_at')->where('status', 1)->orderBy('id', 'Desc')->get();
        return $subscriberData;
    }

    public function headings(): array
    {
        return ['ID', 'EMAIL', 'SUBSCRIBED ON (date)'];
    }
}
