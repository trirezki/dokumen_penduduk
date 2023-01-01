@extends('welcome')
@section('title', 'Surat Izin Usaha')
@section('content')
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form action="/surat" method="POST">
        {{ csrf_field() }}
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control @error('name') border border-danger @enderror">
            <!-- @error('name')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror -->
        </div>
        <div class="mb-3">
            <label for="exampleInputPassword1" class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
            <!-- @error('email')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror -->
            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="exampleCheck1">
            <label class="form-check-label" for="exampleCheck1">Check me out</label>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="/surat" type="submit" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
    {{ $email }}
@endsection