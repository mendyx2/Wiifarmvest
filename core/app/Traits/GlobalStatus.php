<?php

namespace App\Traits;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait GlobalStatus
{
    /**
     * Switch a status column. React pages send what they mean
     * (`state=enable` or `state=disable`), so a second click from a stale
     * tab does not undo the first; without `state` (the Blade pages) it
     * still flips the value.
     */
    public static function changeStatus($id, $column = 'status')
    {
        $modelName = get_class();
        $query     = $modelName::findOrFail($id);
        $state     = request()->input('state');

        if ($state === 'enable' || $state === 'disable') {
            $wanted = $state === 'enable' ? Status::ENABLE : Status::DISABLE;
            if ((int) $query->$column === $wanted) {
                $notify[] = ['info', keyToTitle($column) . ' was already ' . ($wanted ? 'on' : 'off')];
                return back()->withNotify($notify);
            }
            $query->$column = $wanted;
        } elseif ($query->$column == Status::ENABLE) {
            $query->$column = Status::DISABLE;
        } else {
            $query->$column = Status::ENABLE;
        }
        $message       = keyToTitle($column). ' changed successfully';

        $query->save();
        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }


    public function statusBadge(): Attribute
    { 
        return new Attribute(
            get: fn () => $this->badgeData(),
        );
    }

    public function badgeData()
    {
        $html = '';
        if ($this->status == Status::ENABLE) {
            $html = '<span class="badge badge--success">' . trans('Enabled') . '</span>';
        } else {
            $html = '<span class="badge badge--warning">' . trans('Disabled') . '</span>';
        }
        return $html;
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', Status::DISABLE);
    }
}
