<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('address');
            }
        });

        // Create roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $adminRole = Role::create(['name' => 'admin']);

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
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to super admin
        $superAdminRole->givePermissionTo(Permission::all());

        // Assign permissions to admin (exclude karyawan permissions)
        $adminPermissions = Permission::whereNotIn('name', ['view_karyawan', 'create_karyawan', 'update_karyawan', 'delete_karyawan'])->get();

        $adminRole->givePermissionTo($adminPermissions);

        // Create default super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@ramamotor.com',
            'password' => Hash::make('password123'),
            'phone' => '081234567890',
            'address' => 'Jl. Rama Motor No. 1',
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Create default admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@ramamotor.com',
            'password' => Hash::make('password123'),
            'phone' => '081234567891',
            'address' => 'Jl. Rama Motor No. 2',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'is_active']);
        });

        // Delete created users
        User::whereIn('email', ['superadmin@ramamotor.com', 'admin@ramamotor.com'])->delete();
    }
};
