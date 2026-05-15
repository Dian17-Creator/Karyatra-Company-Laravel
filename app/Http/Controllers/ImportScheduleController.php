<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportSchedule;
use App\Models\timport_userschedule;
use Redirect;
use Session;
use DB;

ini_set('max_execution_time', 1200);
ini_set('memory_limit', '512M');
ini_set('post_max_size', '512M');
ini_set('upload_max_filesize', '512M');

class ImportScheduleController extends Controller
{
    public function importSchedule(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,xls,xlsx',]);
        //$this->validate($request, ['file' => 'required|mimes:csv,xls,xlsx']);
        /*if($request->delklaiminaco == 'del'){
    	    XKlaimInaco::query()->delete();
    	};*/
        $fname = $request->file('file')->getClientOriginalName();
        Excel::import(new ImportSchedule(), $request->file('file')->store('temp'));
        return redirect()->route('schedule.index')
            ->with('success', 'Import file ' . $fname . ' berhasil diunggah dan diproses.');
    }
}
