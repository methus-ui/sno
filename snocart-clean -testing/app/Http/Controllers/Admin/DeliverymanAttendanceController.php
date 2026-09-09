<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\DeliverymanAttendance;
use App\Exports\DeliverymanAttendanceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DeliverymanAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $deliveryMen = DeliveryMan::active()->get();
        
        $query = DeliverymanAttendance::with('deliveryMan');
        
        // Filter by delivery man
        if ($request->has('delivery_man_id') && $request->delivery_man_id) {
            $query->where('delivery_man_id', $request->delivery_man_id);
        }
        
        // Filter by date range
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $query->whereBetween('date', [$startDate, $endDate]);
        
        $attendances = $query->orderBy('date', 'desc')->paginate(15);
        
        // Calculate statistics
        $totalWorkingDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $totalPresent = $query->where('status', 'present')->count();
        $totalPartial = $query->where('status', 'partial')->count();
        $totalAbsent = $query->where('status', 'absent')->count();
        
        return view('admin-views.deliveryman.attendance.index', compact(
            'deliveryMen', 
            'attendances', 
            'startDate', 
            'endDate',
            'totalWorkingDays',
            'totalPresent',
            'totalPartial',
            'totalAbsent'
        ));
    }

    public function report()
    {
        $deliveryMen = DeliveryMan::active()->get();
        return view('admin-views.deliveryman.attendance.report', compact('deliveryMen'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:weekly,monthly',
            'export_type' => 'required|in:excel,csv',
        ]);

        $deliveryManId = $request->delivery_man_id;
        $reportType = $request->report_type;
        
        // Calculate date range based on report type
        if ($reportType === 'weekly') {
            $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
        } else {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $attendances = DeliverymanAttendance::getAttendanceReport($deliveryManId, $startDate, $endDate);
        
        $data = [
            'attendances' => $attendances,
            'delivery_man' => $deliveryManId ? DeliveryMan::find($deliveryManId) : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'report_type' => $reportType,
        ];

        $fileName = 'deliveryman_attendance_' . $reportType . '_' . now()->format('Y_m_d_H_i_s');
        
        if ($request->export_type === 'csv') {
            $fileName .= '.csv';
            return Excel::download(new DeliverymanAttendanceExport($data), $fileName, \Maatwebsite\Excel\Excel::CSV);
        } else {
            $fileName .= '.xlsx';
            return Excel::download(new DeliverymanAttendanceExport($data), $fileName);
        }
    }
}