<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementModel;
use Illuminate\Http\Request;
use App\Models\ExamScheduleModel;
use App\Models\ExamResultModel;
use App\Models\StudentModel;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function dashboard()
    {
    $schedules = ExamScheduleModel::join('exam_result', 'exam_schedule.shcedule_id', '=', 'exam_result.schedule_id')
        ->where('exam_result.user_id', auth()->id())
        ->select('exam_schedule.*')
        ->paginate(10);
        $examResults = ExamResultModel::where('user_id', auth()->id())->latest()->first(); // only the latest score for logged in user
        $type_menu = 'dashboard';
        $announcements = AnnouncementModel::where('announcement_status', 'published')->latest()->first();

        // Check if student profile is complete
        $student = StudentModel::where('user_id', auth()->id())->first();
        $isComplete = true;
        $missingFiles = [];

        if (!$student || !$student->photo) {
            $isComplete = false;
            $missingFiles[] = 'Photo';
        }

        if (!$student || !$student->ktp_scan) {
            $isComplete = false;
            $missingFiles[] = 'ID Card (KTP)';
        }

        if (!$student || !$student->home_address) {
            $isComplete = false;
            $missingFiles[] = 'Home Address';
        }

        // Check if the score is below or equal to 70
        $examFailed = false;
        if ($examResults && $examResults->score <= 70) {
            $examFailed = true;
        }

        return view('users-student.student-dashboard', compact(
            'schedules',
            'type_menu',
            'examResults',
            'announcements',
            'examFailed',
            'isComplete',
            'missingFiles'
        ));
    }

    public function profile()
    {
        $type_menu = 'profile'; // or fill as needed
        return view('users-student.student-profile', compact('type_menu'));
    }

    public function list(Request $request)
    {
        $students = StudentModel::select('student_id', 'user_id', 'name', 'nim', 'study_program', 'major', 'campus', 'ktp_scan', 'ktm_scan', 'photo', 'home_address', 'current_address')
            ->with('user');

        if ($request->student_id) {
            $students->where('student_id', $request->student_id);
        };

        return DataTables::of($students)
            ->addIndexColumn()
            ->addColumn('action', function ($student) {
                $btn = '<button onclick="modalAction(\'' . url('/manage-users/student/' . $student->student_id . '/show_ajax') . '\')" class="btn btn-info btn-sm">Detail</button> ';
                $btn .= '<button onclick="modalAction(\'' . url('/manage-users/student/' . $student->student_id . '/edit_ajax') . '\')" class="btn btn-warning btn-sm">Edit</button> ';
                $btn .= '<button onclick="modalAction(\'' . url('/manage-users/student/' . $student->student_id . '/delete_ajax') . '\')" class="btn btn-danger btn-sm">Delete</button> ';
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit_ajax(string $id)
    {
        $student = StudentModel::find($id);

        return view('users-admin.manage-user.student.edit', ['student' => $student]);
    }

    public function update_ajax(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'home_address' => 'required|string',
            'current_address' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'msgField' => $validator->errors()
            ]);
        }

        $student = StudentModel::find($id);
        if ($student) {
            $data = $request->only([
                'name',
                'home_address',
                'current_address'
            ]);

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('photos', 'public');
            }

            $student->update($data);
            return response()->json([
                'status' => true,
                'message' => 'Data mahasiswa berhasil diupdate'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ]);
        }
    }

    public function confirm_ajax(string $id)
    {
        $student = StudentModel::find($id);

        return view('users-admin.manage-user.student.delete', ['student' => $student]);
    }

    public function delete_ajax(string $id)
    {
        $student = StudentModel::find($id);
        if ($student) {
            $student->delete();

            // Choose one response type - JSON for AJAX
            return response()->json([
                'status' => true,
                'message' => 'Student data has been successfully deleted.'
            ]);

            // Alternative if using regular form submission:
            // return redirect('/manage-users/student/');
        } else {
            // Choose one response type - JSON for AJAX
            return response()->json([
                'status' => false,
                'message' => 'Data not found.'
            ]);

            // Alternative if using regular form submission:
            // return redirect('/manage-users/student/')->with('error', 'Data not found');
        }
    }

    public function show_ajax(string $id)
    {
        $student = StudentModel::with('user')->find($id);

        return view('users-admin.manage-user.student.show', [
            'student' => $student
        ]);
    }
}
