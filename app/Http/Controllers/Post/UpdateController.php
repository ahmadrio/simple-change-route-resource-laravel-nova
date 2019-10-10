<?php

namespace App\Http\Controllers\Post;

use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;
use App\PostLog;

class UpdateController extends Controller
{
    /**
     * Create a new resource.
     *
     * @param  \Laravel\Nova\Http\Requests\UpdateResourceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(UpdateResourceRequest $request)
    {
        [$model, $resource] = DB::transaction(function () use ($request) {
            $model = $request->findModelQuery()->lockForUpdate()->firstOrFail();

            $resource = $request->newResourceWith($model);
            $resource->authorizeToUpdate($request);
            $resource::validateForUpdate($request);

            if ($this->modelHasBeenUpdatedSinceRetrieval($request, $model)) {
                return response('', 409)->throwResponse();
            }

            [$model, $callbacks] = $resource::fillForUpdate($request, $model);

            ActionEvent::forResourceUpdate($request->user(), $model)->save();

            $model->save();

            // auto create on post_logs table
            PostLog::create([
                'post_id' => $model->id,
                'user_id' => auth()->id(),
                'action' => 'create',
                'details' => json_encode($model->getOriginal())
            ]);

            collect($callbacks)->each->__invoke();

            return [$model, $resource];
        });

        return response()->json([
            'id' => $model->getKey(),
            'resource' => $model->attributesToArray(),
            'redirect' => $resource::redirectAfterUpdate($request, $resource),
        ]);
    }

    /**
     * Determine if the model has been updated since it was retrieved.
     *
     * @param  \Laravel\Nova\Http\Requests\UpdateResourceRequest  $request
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function modelHasBeenUpdatedSinceRetrieval(UpdateResourceRequest $request, $model)
    {
        $column = $model->getUpdatedAtColumn();

        if (! $model->{$column}) {
            return false;
        }

        return $request->input('_retrieved_at') && $model->usesTimestamps() && $model->{$column}->gt(
            Carbon::createFromTimestamp($request->input('_retrieved_at'))
        );
    }
}