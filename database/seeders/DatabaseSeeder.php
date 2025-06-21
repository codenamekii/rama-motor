<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles first
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create permissions
        $permissions = [
            // Barang
            'view_barang',
            'create_barang',
            'update_barang',
            'delete_barang',

            // Jenis Barang
            'view_jenis_barang',
            'create_jenis_barang',
            'update_jenis_barang',
            'delete_jenis_barang',

            // Satuan Barang
            'view_satuan_barang',
            'create_satuan_barang',
            'update_satuan_barang',
            'delete_satuan_barang',

            // Merk Barang
            'view_merk_barang',
            'create_merk_barang',
            'update_merk_barang',
            'delete_merk_barang',

            // Transaksi Masuk
            'view_transaksi_masuk',
            'create_transaksi_masuk',
            'update_transaksi_masuk',
            'delete_transaksi_masuk',
            'export_transaksi_masuk',

            // Transaksi Keluar
            'view_transaksi_keluar',
            'create_transaksi_keluar',
            'update_transaksi_keluar',
            'delete_transaksi_keluar',
            'export_transaksi_keluar',

            // Pelanggan
            'view_pelanggan',
            'create_pelanggan',
            'update_pelanggan',
            'delete_pelanggan',

            // Pemasok
            'view_pemasok',
            'create_pemasok',
            'update_pemasok',
            'delete_pemasok',

            // Karyawan (only for super admin)
            'view_karyawan',
            'create_karyawan',
            'update_karyawan',
            'delete_karyawan',

            // Dashboard & Reports
            'view_dashboard',
            'view_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super admin
        $superAdminRole->syncPermissions(Permission::all());

        // Assign permissions to admin (exclude karyawan permissions)
        $adminPermissions = Permission::whereNotIn('name', ['view_karyawan', 'create_karyawan', 'update_karyawan', 'delete_karyawan'])
            ->pluck('name')
            ->toArray();

        $adminRole->syncPermissions($adminPermissions);

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@ramamotor.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'phone' => '081234567890',
                'address' => 'Jl. Rama Motor No. 1',
                'is_active' => true,
            ],
        );
        $superAdmin->assignRole('super_admin');

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@ramamotor.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'phone' => '081234567891',
                'address' => 'Jl. Rama Motor No. 2',
                'is_active' => true,
            ],
        );
        $admin->assignRole('admin');

        $this->command->info('Seeding completed!');
        $this->command->info('Super Admin: superadmin@ramamotor.com / password123');
        $this->command->info('Admin: admin@ramamotor.com / password123');
    }
}
