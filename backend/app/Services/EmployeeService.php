<?php

namespace App\Services;

use App\Traits\FileManagerTrait;

class EmployeeService
{
    use FileManagerTrait;

    public function getAddData(Object $request): array
    {
        $data = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => bcrypt($request->password),
            'image' => $this->upload('admin/', 'png', $request->file('image')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Handle face data if provided
        if ($request->filled('face_data')) {
            $data['face_data'] = $request->face_data;
            $data['face_registered'] = true;
            $data['face_registered_at'] = now();
        }

        return $data;
    }
    public function getUpdateData(Object $request, object $employee): array
    {
        if ($request['password'] == null) {
            $pass = $employee['password'];
        } else {
            $pass = bcrypt($request['password']);
            $employee->remember_token=null;
        }

        if ($request->has('image')) {
            $employee['image'] = $this->updateAndUpload('admin/', $employee->image, 'png', $request->file('image'));
        }

        $data = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $pass,
            'image' => $employee['image'],
            'updated_at' => now(),
            'is_logged_in' => 0,
        ];

        // Handle face data if provided
        if ($request->filled('face_data')) {
            $data['face_data'] = $request->face_data;
            $data['face_registered'] = true;
            $data['face_registered_at'] = now();
        }

        return $data;
    }
    public function adminCheck(Object $employee): array
    {
        if (auth('admin')->id()  != $employee['id']){
            return ['flag' => 'unauthorized'];
        }
        return ['flag' => 'authorized'];
    }

}
