@extends("sharp::layout")

@section("content")

    <x-sharp::page class="show">
        <sharp-action-view context="show">
            <router-view></router-view>
        </sharp-action-view>
    </x-sharp::page>

@endsection
