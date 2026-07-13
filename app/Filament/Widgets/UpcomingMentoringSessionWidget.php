<?php

namespace App\Filament\Widgets;

use App\Models\Cts\Session;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingMentoringSessionWidget extends Widget
{
    protected static ?int $sort = -4;

    protected static bool $isLazy = false;

    protected string|int|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.upcoming-mentoring-session-widget';

    public ?Session $session = null;

    public ?Carbon $startsAt = null;

    public ?Carbon $endsAt = null;

    public int $remainingSessions = 0;

    public static function canView(): bool
    {
        return Filament::auth()->check()
            && Filament::auth()->user()?->member !== null;
    }

    public function mount(): void
    {
        $member = Filament::auth()->user()?->member;

        if (! $member) {
            return;
        }

        $sessions = Session::query()
            ->with('mentor')
            ->where('student_id', $member->id)
            ->whereNull('filed')
            ->whereNull('cancelled_datetime')
            ->where('taken', 1)
            ->where('noShow', 0)
            ->where(function (Builder $query): void {
                $query->whereDate('taken_date', '>', today())->orWhere(function (Builder $query): void {
                    $query->whereDate('taken_date', today())->whereTime('taken_to', '>', now()->format('H:i:s'));
                });
            })
            ->orderBy('taken_date')
            ->orderBy('taken_from')
            ->get();

        $this->session = $sessions->first();
        $this->remainingSessions = max($sessions->count() - 1, 0);

        if (! $this->session) {
            return;
        }

        $date = Carbon::parse($this->session->taken_date)->toDateString();

        $this->startsAt = Carbon::parse(
            "{$date} {$this->session->taken_from}"
        );

        $this->endsAt = Carbon::parse(
            "{$date} {$this->session->taken_to}"
        );

    }

    public function shouldRender(): bool
    {
        return $this->session !== null;
    }
}
