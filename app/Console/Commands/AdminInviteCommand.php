<?php

namespace App\Console\Commands;

use App\Models\AdminInvite;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AdminInviteCommand extends Command
{
    protected $signature = 'admin:invite {email} {--role=admin} {--expires-days=7}';

    protected $description = 'Generate a one-time registration invite link for a new admin portal user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->option('role');
        $expiresDays = (int) $this->option('expires-days');

        $validator = validator(
            ['email' => $email, 'role' => $role],
            ['email' => ['required', 'email'], 'role' => ['required', 'in:admin,user']]
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $invite = AdminInvite::create([
            'token' => Str::random(48),
            'email' => $email,
            'role' => $role,
            'expires_at' => now()->addDays($expiresDays),
        ]);

        $url = url('/auth/register?invite='.$invite->token);

        $this->info("Invite created for {$email} (role: {$role}), expires in {$expiresDays} day(s).");
        $this->line($url);

        return self::SUCCESS;
    }
}
