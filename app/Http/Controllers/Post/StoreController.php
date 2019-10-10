<?php

namespace App\Http\Controllers\Post;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Http\Requests\CreateResourceRequest;
use App\PostLog;

class StoreController extends Controller
{
    /**
     * Create a new resource.
     *
     * @param  \Laravel\Nova\Http\Requests\CreateResourceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(CreateResourceRequest $request)
    {
        $resource = $request->resource();

        $resource::authorizeToCreate($request);

        $resource::validateForCreation($request);

        $model = DB::transaction(function () use ($request, $resource) {
            [$model, $callbacks] = $resource::fill(
                $request, $resource::newModel()
            );

            if ($request->viaRelationship()) {
                $request->findParentModelOrFail()
                        ->{$request->viaRelationship}()
                        ->save($model);
            } else {
                $model->save();
            }

            ActionEvent::forResourceCreate($request->user(), $model)->save();

            // auto create on post_logs table
            PostLog::create([
                'post_id' => $model->id,
                'user_id' => auth()->id(),
                'action' => 'create',
                'details' => json_encode($model->getOriginal())
            ]);

            collect($callbacks)->each->__invoke();

            return $model;
        });

        return response()->json([
            'id' => $model->getKey(),
            'resource' => $model->attributesToArray(),
            'redirect' => $resource::redirectAfterCreate($request, $request->newResourceWith($model)),
        ], 201);
    }
}
