<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Purge du cache des permissions de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Définition des permissions par domaine fonctionnel
        $permissions = [
            // Gestion des utilisateurs et rôles
            'users.view',
            'users.manage',

            // Produits financiers
            'products.manage',

            // Conversations WhatsApp
            'conversations.view_all',
            'conversations.view_assigned',

            // Tickets et plaintes
            'tickets.view_all',
            'tickets.view_assigned',
            'tickets.assign',
            'tickets.comment_internal',
            'tickets.comment_public',

            // Logs et configuration
            'logs.view',
            'config.manage',

            // CRM Natif
            'crm.contacts.view',
            'crm.campaigns.manage',
            'crm.reports.view',
            'crm.tags.manage',
            'crm.alerts.view',
        ];

        // Création ou récupération des permissions (idempotent)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Création ou récupération des rôles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $agentRole      = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);

        // Le rôle super-admin recevra toutes les permissions implicitement via Gate::before.

        // Assignation des permissions pour le rôle Admin
        $adminRole->syncPermissions([
            'users.view', 'users.manage',
            'products.manage',
            'conversations.view_all',
            'tickets.view_all', 'tickets.assign', 'tickets.comment_internal', 'tickets.comment_public',
            'logs.view',
            'crm.contacts.view', 'crm.campaigns.manage', 'crm.reports.view', 'crm.tags.manage', 'crm.alerts.view',
        ]);

        // Assignation des permissions pour le rôle Supervisor
        $supervisorRole->syncPermissions([
            'conversations.view_all',
            'tickets.view_all', 'tickets.assign', 'tickets.comment_internal', 'tickets.comment_public',
            'crm.contacts.view', 'crm.campaigns.manage', 'crm.reports.view', 'crm.alerts.view',
        ]);

        // Assignation des permissions pour le rôle Agent
        $agentRole->syncPermissions([
            'conversations.view_assigned',
            'tickets.view_assigned', 'tickets.comment_internal', 'tickets.comment_public',
            'crm.contacts.view', 'crm.alerts.view',
        ]);
    }
}
