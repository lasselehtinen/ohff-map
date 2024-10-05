<!DOCTYPE html>
<html>
<head>
    <title>Suggest an OHFF reference</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
  <div class="container mt-4">
  @if(session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
  @endif

  @if($countForThisYear >= $limit)
  <div class="alert alert-danger" role="alert">
  The limit for suggestions for the year {{ $currentYear }} has been reached. New suggestions will not be approved until the beginning of next year!
  </div>
  @endif

  <div class="alert alert-dark" role="alert">
  {{ $countForThisYear }} has been suggested for year {{ $currentYear }}.
  @if($countForThisYear >= $limit)
  The overdue will be handled during the next year.
  @endif
  </div>
  
  @if($countForThisYear <= $limit)
  <div class="card">
    <div class="card-header text-center font-weight-bold">
      Suggest an OHFF reference
    </div>
    <div class="card-body">
      <form name="suggest-reference-form" id="suggest-reference-form" method="post" action="{{url('store-reference')}}">
       @csrf
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" class="form-control @if ($errors->has('name')) is-invalid @endif" id="name" name="name" value="{{ old('name') }}">
          @if ($errors->has('name'))
          <div class="invalid-feedback">
            {{ $errors->first('name') }}
          </div>
          @endif
        </div>

        <div class="form-group">
          <label for="coordinates">Coordinates</label>
          <input type="text" class="form-control @if ($errors->has('coordinates')) is-invalid @endif" id="coordinates" name="coordinates" value="{{ old('coordinates') }}">
          <small id="coordinatesHelpBlock" class="form-text text-muted">
          Type coordinates in format <em>latitude,longitude</em>. For example <em>67.83894,25.50700</em>.
          </small>
          @if ($errors->has('coordinates'))
          <div class="invalid-feedback">
            {{ $errors->first('coordinates') }}
          </div>
          @endif
        </div>

        <div class="form-group">
          <label for="protected_planet_link">Protected Planet link</label>
          <input type="text" class="form-control @if ($errors->has('protected_planet_link')) is-invalid @endif" id="protected_planet_link" name="protected_planet_link" value="{{ old('protected_planet_link') }}">
          @if ($errors->has('protected_planet_link'))
          <div class="invalid-feedback">
            {{ $errors->first('protected_planet_link') }}
          </div>
          @endif
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
    </div>
  </div>
  @endif

</div>  
</body>
</html>