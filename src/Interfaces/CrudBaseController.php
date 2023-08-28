<?php

namespace Microcrud\Interfaces;

use Illuminate\Http\Request;
use Microcrud\Requests\DestroyRequest;
use Microcrud\Requests\PaginationRequest;
use Microcrud\Requests\ShowRequest;

interface CrudBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request  $request
     *
     */
    function index(Request $request);
    /**
     * Show resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function show(Request $request);

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function store(Request $request);

    /**
     * Update resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function update(Request $request);

    /**
     * Delete resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function destroy(Request $request);

    //Controller for client API

    /**
     * Enlist all information.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function findAll(Request $request);
    /**
     * Show one information
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    function findOne(Request $request);
}
