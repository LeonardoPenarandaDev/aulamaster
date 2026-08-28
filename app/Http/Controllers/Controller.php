<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Delete a model, converting a foreign-key restriction (records that
     * still reference it, e.g. an evaluation with results or a level with
     * enrollments) into a friendly redirect instead of a raw 500 error.
     */
    protected function deleteOrBlock(Model $model, string $blockedMessage): ?RedirectResponse
    {
        try {
            $model->delete();

            return null;
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return back()->with('error', $blockedMessage);
        }
    }
}
