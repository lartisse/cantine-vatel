<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Durcissement de la sécurité :
 * - users.must_change_password : mot de passe provisoire à changer à la première connexion ;
 * - journal d'audit verrouillé au niveau de la base (déclencheurs) : aucune suppression,
 *   aucune modification du contenu (seul actor_id peut passer à NULL quand un compte est supprimé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('must_change_password')->default(false)->after('password');
        });

        $fields = ['action', 'actor_name', 'actor_role', 'target_type', 'target_id', 'target_label', 'details', 'ip', 'created_at'];
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $changed = implode(' OR ', array_map(fn ($f) => "NEW.$f IS NOT OLD.$f", $fields));
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs WHEN $changed BEGIN SELECT RAISE(ABORT, 'Le journal d''audit ne peut pas être modifié'); END;");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs BEGIN SELECT RAISE(ABORT, 'Le journal d''audit ne peut pas être supprimé'); END;");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $changed = implode(' OR ', array_map(fn ($f) => "NOT (NEW.$f <=> OLD.$f)", $fields));
            DB::unprepared("CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW BEGIN IF $changed THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal d''audit ne peut pas être modifié'; END IF; END");
            DB::unprepared("CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le journal d''audit ne peut pas être supprimé'");
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_no_delete');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('must_change_password'));
    }
};
