<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TblUserSeeder extends Seeder
{
    /**
     * Seed tbl_user with legacy/sample users (e.g. from ehris_db_clean.sql).
     */
    public function run(): void
    {
        if (DB::table('tbl_user')->exists()) {
            return;
        }

        $now = now()->format('Y-m-d');
        $password = Hash::make('1234');

        $users = [
            [
                'hrId' => 10001,
                'email' => 'juan.santos@deped.gov.ph',
                'password' => $password,
                'lastname' => 'Santos',
                'firstname' => 'Juan',
                'middlename' => 'Torres',
                'extname' => null,
                'avatar' => 'avatar-default.jpg',
                'job_title' => 'Information Technology Officer I',
                'role' => 'Employee',
                'active' => 1,
                'date_created' => $now,
                'fullname' => 'Juan Torres Santos',
                'department_id' => 100104,
            ],
            [
                'hrId' => 10002,
                'email' => 'maria.reyes@deped.gov.ph',
                'password' => $password,
                'lastname' => 'Reyes',
                'firstname' => 'Maria',
                'middlename' => 'Cabrera',
                'extname' => null,
                'avatar' => 'avatar-default.jpg',
                'job_title' => 'Administrative Officer II',
                'role' => 'Employee',
                'active' => 1,
                'date_created' => $now,
                'fullname' => 'Maria Cabrera Reyes',
                'department_id' => 100107,
            ],
            [
                'hrId' => 10003,
                'email' => 'carlo.dizon@deped.gov.ph',
                'password' => $password,
                'lastname' => 'Dizon',
                'firstname' => 'Carlo',
                'middlename' => 'Mendoza',
                'extname' => null,
                'avatar' => 'avatar-default.jpg',
                'job_title' => 'Teacher I',
                'role' => 'Employee',
                'active' => 1,
                'date_created' => $now,
                'fullname' => 'Carlo Mendoza Dizon',
                'department_id' => 128164,
            ],
            [
                'hrId' => 10004,
                'email' => 'angelica.villanueva@deped.gov.ph',
                'password' => $password,
                'lastname' => 'Villanueva',
                'firstname' => 'Angelica',
                'middlename' => 'May',
                'extname' => null,
                'avatar' => 'avatar-default.jpg',
                'job_title' => 'Nurse II',
                'role' => 'Employee',
                'active' => 1,
                'date_created' => $now,
                'fullname' => 'Angelica May Villanueva',
                'department_id' => 100303,
            ],
            [
                'hrId' => 10005,
                'email' => 'mark.bacolod@deped.gov.ph',
                'password' => $password,
                'lastname' => 'Bacolod',
                'firstname' => 'Mark',
                'middlename' => 'Santos',
                'extname' => null,
                'avatar' => 'avatar-default.jpg',
                'job_title' => 'School Principal I',
                'role' => 'Employee',
                'active' => 1,
                'date_created' => $now,
                'fullname' => 'Mark Santos Bacolod',
                'department_id' => 304167,
            ],
        ];

        DB::table('tbl_user')->insert($users);
    }
}
