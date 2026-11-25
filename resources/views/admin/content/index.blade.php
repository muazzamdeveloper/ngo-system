@extends('layouts.admin')
@section('title', 'Website pages')
@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Website pages</h1>
            <p class="page-subtitle">Edit the text and images of each public page. Every change shows on the website straight after saving.</p>
        </div>
    </div>
    <div class="card table-card">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Page</th><th>Address</th><th class="col-actions">Edit</th></tr></thead>
                <tbody>
                    @foreach ($pages as $key => $def)
                        <tr>
                            <td class="strong">{{ $def['title'] }}</td>
                            <td><code>/page/{{ $key }}</code></td>
                            <td><a class="btn btn-small btn-light" href="{{ route('content.edit', $key) }}">Edit content</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
