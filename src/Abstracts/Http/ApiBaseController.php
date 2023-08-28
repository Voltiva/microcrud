<?php

namespace Microcrud\Abstracts\Http;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use stdClass;
use Microcrud\Interfaces\ApiController;

abstract class ApiBaseController implements ApiController
{
    public function created($resource, $item)
    {
        return $this->singleItem($resource, $item, 201);
    }

    public function accepted($resource, $item)
    {
        return $this->singleItem($resource, $item, 202);
    }

    public function singleItem($resource, $item, $status_code = 200)
    {
        return response()->json([
            "data" => new $resource($item)
        ], $status_code);
    }

    public function paginated($resource, $items, $status_code = 200)
    {
        return response()->json([
            'pagination' => [
                'current' => $items->currentPage(),
                'previous' => $items->currentPage() > 1 ? $items->currentPage() - 1 : 0,
                'next' => $items->hasMorePages() ? $items->currentPage() + 1 : 0,
                'perPage' => $items->perPage(),
                'totalPage' => $items->lastPage(),
                'totalItem' => $items->total(),
            ],
            'data' => $resource::collection($items->items())
        ], $status_code);
    }

    protected function paginateQuery($resource, $modelQuery, $modelTableName = '', $status_code = 200)
    {
        $limit = request()->limit ?? 10;
        $data = request()->all();
        ksort($data);
        $item_key = request()->path() . ":" . $modelTableName . ":" . serialize($data);
        $items = Cache::tags([$modelTableName])
            ->remember(
                $item_key,
                Carbon::now()->addDay(),
                function () use ($modelQuery, $limit) {
                    return $modelQuery->paginate($limit);
                }
            );
        return response()->json([
            'pagination' => [
                'current' => $items->currentPage(),
                'previous' => $items->currentPage() > 1 ? $items->currentPage() - 1 : 0,
                'next' => $items->hasMorePages() ? $items->currentPage() + 1 : 0,
                'perPage' => $items->perPage(),
                'totalPage' => $items->lastPage(),
                'totalItem' => $items->total(),
            ],
            'data' => $resource::collection($items->items())
        ], $status_code);
    }

    public function success($message = 'Success', $status_code = 200)
    {
        return response()->json([
            'message' => __($message)
        ], $status_code);
    }

    public function noContent()
    {
        return response()->noContent();
    }

    public function error($message, $status_code = 400, $exception = null)
    {
        if ($exception) {
            Log::error('MESSAGE: ' . $message . ' ERROR: ' . $exception->getMessage() . ' TRACE: ' . $exception->getTraceAsString());
        } else {
            Log::error('MESSAGE: ' . $message);
        }
        if (Lang::has('microcrud_translations::errors.' . $message) || Lang::has('microcrud_translations::errors.' . $message)) {
            return response()->json([
                'message'   => $this->translate('errors.' . $message),
            ], $status_code);
        }
        return response()->json([
            'message'   => $message,
        ], $status_code);
    }

    public function errorNotFound($message = 'Not Found', $exception = null)
    {
        return $this->error($message, 404, $exception);
    }

    public function errorBadRequest($message = 'Bad Request', $exception = null)
    {
        return $this->error($message, 400, $exception);
    }

    public function errorForbidden($message = 'Forbidden', $exception = null)
    {
        return $this->error($message, 403, $exception);
    }

    public function errorUnauthorized($message = 'Unauthorized', $exception = null)
    {
        return $this->error($message, 401, $exception);
    }

    public function translate(String $key)
    {
        return trans('microcrud_translations::' . $key);
    }
}
