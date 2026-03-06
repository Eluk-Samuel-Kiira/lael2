<?php
// app/Http/Controllers/ArtisanCommandController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Artisan, Auth };
use App\Models\User;
use Spatie\Permission\Models\Role;

class ArtisanCommandController extends Controller
{

    // Whitelist of allowed commands for security
    protected $allowedCommands = [
        'storage:link'         => 'Create storage symlink',
        'cache:clear'          => 'Clear application cache',
        'config:clear'         => 'Clear config cache',
        'config:cache'         => 'Cache configuration',
        'route:clear'          => 'Clear route cache',
        'route:cache'          => 'Cache routes',
        'view:clear'           => 'Clear compiled views',
        'optimize:clear'       => 'Clear all cached data',
        'optimize'             => 'Cache config, routes & views',
        'queue:restart'        => 'Restart queue workers',
        'migrate'              => 'Run database migrations',
        'migrate:status'       => 'Show migration status',
        'db:seed'              => 'Seed the database',
        'migrate:fresh --seed' => 'Migrate and Seed the database a fresh',
    ];

    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }
        return view('artisan.index', [
            'commands' => $this->allowedCommands,
        ]);
    }

    public function run(Request $request)
    {
        
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'command' => 'required|string',
        ]);

        $command = $request->input('command');

        // Security check — only allow whitelisted commands
        if (!array_key_exists($command, $this->allowedCommands)) {
            return response()->json([
                'success' => false,
                'output'  => '❌ Command not allowed.',
            ], 403);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'command' => 'php artisan ' . $command,
                'output'  => $output ?: '✅ Command executed successfully with no output.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'command' => 'php artisan ' . $command,
                'output'  => '❌ Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}