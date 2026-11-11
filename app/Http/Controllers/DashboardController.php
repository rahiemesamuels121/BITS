<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Illustrative inventory only. No connection to production infrastructure.
        $systems = [
            ['id' => 'SYS-001', 'name' => 'Production web 01', 'hostname' => 'web-01.internal', 'type' => 'Web server', 'environment' => 'Production', 'status' => 'Healthy', 'cpu' => 42, 'memory' => 68, 'uptime' => '45d 12h', 'os' => 'Ubuntu 24.04 LTS', 'ip' => '10.0.10.11'],
            ['id' => 'SYS-002', 'name' => 'Production web 02', 'hostname' => 'web-02.internal', 'type' => 'Web server', 'environment' => 'Production', 'status' => 'Healthy', 'cpu' => 36, 'memory' => 54, 'uptime' => '45d 12h', 'os' => 'Ubuntu 24.04 LTS', 'ip' => '10.0.10.12'],
            ['id' => 'SYS-003', 'name' => 'Primary database', 'hostname' => 'db-01.internal', 'type' => 'Database', 'environment' => 'Production', 'status' => 'Critical', 'cpu' => 91, 'memory' => 94, 'uptime' => '12d 04h', 'os' => 'Ubuntu 24.04 LTS', 'ip' => '10.0.20.10'],
            ['id' => 'SYS-004', 'name' => 'Backup storage', 'hostname' => 'backup-01.internal', 'type' => 'Storage', 'environment' => 'Production', 'status' => 'Warning', 'cpu' => 77, 'memory' => 84, 'uptime' => '28d 09h', 'os' => 'Debian 12', 'ip' => '10.0.30.10'],
            ['id' => 'SYS-005', 'name' => 'Staging application', 'hostname' => 'stage-01.internal', 'type' => 'Application', 'environment' => 'Staging', 'status' => 'Healthy', 'cpu' => 18, 'memory' => 32, 'uptime' => '06d 18h', 'os' => 'Ubuntu 24.04 LTS', 'ip' => '10.0.40.10'],
            ['id' => 'SYS-006', 'name' => 'Office gateway', 'hostname' => 'gateway-01.internal', 'type' => 'Network', 'environment' => 'Production', 'status' => 'Healthy', 'cpu' => 24, 'memory' => 41, 'uptime' => '62d 03h', 'os' => 'OPNsense', 'ip' => '10.0.1.1'],
        ];

        $tickets = [
            ['id' => 'RB-2048', 'subject' => 'High database response time', 'category' => 'Database', 'priority' => 'High', 'status' => 'In progress', 'assignee' => 'Alex Morgan', 'updated' => '12 minutes ago'],
            ['id' => 'RB-2047', 'subject' => 'Nightly backup needs attention', 'category' => 'Storage', 'priority' => 'Medium', 'status' => 'Open', 'assignee' => 'Jamie Davis', 'updated' => '28 minutes ago'],
            ['id' => 'RB-2046', 'subject' => 'Workspace access for new starter', 'category' => 'Access', 'priority' => 'Low', 'status' => 'Waiting', 'assignee' => 'Sam Rivera', 'updated' => '1 hour ago'],
            ['id' => 'RB-2045', 'subject' => 'Renew staging certificate', 'category' => 'Security', 'priority' => 'Low', 'status' => 'Resolved', 'assignee' => 'Alex Morgan', 'updated' => '2 hours ago'],
        ];

        $members = [
            ['name' => 'Alex Morgan', 'email' => 'alex@example.com', 'initials' => 'AM', 'role' => 'Admin', 'status' => 'Active', 'last_active' => '12 minutes ago'],
            ['name' => 'Jamie Davis', 'email' => 'jamie@example.com', 'initials' => 'JD', 'role' => 'Engineer', 'status' => 'Active', 'last_active' => '28 minutes ago'],
            ['name' => 'Sam Rivera', 'email' => 'sam@example.com', 'initials' => 'SR', 'role' => 'Engineer', 'status' => 'Active', 'last_active' => '1 hour ago'],
            ['name' => 'Taylor Chen', 'email' => 'taylor@example.com', 'initials' => 'TC', 'role' => 'Viewer', 'status' => 'Active', 'last_active' => 'Yesterday'],
            ['name' => 'Jordan Lee', 'email' => 'jordan@example.com', 'initials' => 'JL', 'role' => 'Viewer', 'status' => 'Invited', 'last_active' => 'Not yet'],
        ];

        $events = [
            ['time' => '09:42', 'title' => 'Database utilisation threshold exceeded', 'description' => 'Primary database · CPU reached 91%', 'type' => 'Critical', 'icon' => 'activity'],
            ['time' => '09:26', 'title' => 'Nightly backup requires review', 'description' => 'Backup storage · Job completed with a warning', 'type' => 'Warning', 'icon' => 'server'],
            ['time' => '09:10', 'title' => 'Workspace access request assigned', 'description' => 'RB-2046 · Assigned to Sam Rivera', 'type' => 'Info', 'icon' => 'users'],
            ['time' => '08:55', 'title' => 'Staging certificate renewed', 'description' => 'RB-2045 · Resolved by Alex Morgan', 'type' => 'Success', 'icon' => 'shield'],
            ['time' => '08:30', 'title' => 'Scheduled inventory check completed', 'description' => '6 systems checked · Inventory up to date', 'type' => 'Success', 'icon' => 'check'],
        ];

        return view('admin.dashboard', compact('systems', 'tickets', 'members', 'events'));
    }
}
