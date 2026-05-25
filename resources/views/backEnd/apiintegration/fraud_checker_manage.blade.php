@extends('backEnd.layouts.master') 
@section('title','Fraud Checker API')
@section('css')
<style>
  .form-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
  }
  .api-info {
    background: #e7f3ff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
  }
  .code-example {
    background: #2d3748;
    color: #f7fafc;
    border-radius: 8px;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    overflow-x: auto;
    white-space: pre-wrap;
  }
</style>
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection 

@section('content')
<div class="container-fluid">
  <!-- start page title -->
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <h4 class="page-title">Fraud Checker API Configuration</h4>
      </div>
    </div>
  </div>
  <!-- end page title -->

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <form action="{{route('admin.fraud-checker-api.update')}}" method="POST" class="row" data-parsley-validate="">
            @csrf
            
            <div class="col-sm-6">
              <div class="form-group mb-3">
                <label for="name" class="form-label">API Name *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" 
                       value="{{ $fraudApi->name ?? 'Fraud Checker API' }}" id="name" required="" />
                @error('name')
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>
            <!-- col-end -->

            <div class="col-sm-6">
              <div class="form-group mb-3">
                <label for="api_url" class="form-label">API URL *</label>
                <input type="url" class="form-control @error('api_url') is-invalid @enderror" name="api_url" 
                       value="{{ $fraudApi->api_url ?? 'https://your-domain.com/api/check' }}" id="api_url" required="" 
                       placeholder="https://your-domain.com/api/check" />
                @error('api_url')
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>
            <!-- col-end -->

            <div class="col-sm-6">
              <div class="form-group mb-3">
                <label for="api_key" class="form-label">API Key *</label>
                <input type="text" class="form-control @error('api_key') is-invalid @enderror" name="api_key" 
                       value="{{ $fraudApi->api_key ?? '' }}" id="api_key" required="" 
                       placeholder="your_api_key_here" />
                @error('api_key')
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>
            <!-- col-end -->

            <div class="col-sm-6">
              <div class="form-group mb-3">
                <label for="query_type" class="form-label">Query Type *</label>
                <select class="form-control @error('query_type') is-invalid @enderror" name="query_type" id="query_type" required="">
                  <option value="basic" {{ ($fraudApi->query_type ?? 'basic') == 'basic' ? 'selected' : '' }}>Basic</option>
                  <option value="detailed" {{ ($fraudApi->query_type ?? '') == 'detailed' ? 'selected' : '' }}>Detailed</option>
                  <option value="comprehensive" {{ ($fraudApi->query_type ?? '') == 'comprehensive' ? 'selected' : '' }}>Comprehensive</option>
                </select>
                @error('query_type')
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>
            <!-- col-end -->

            <div class="col-sm-12">
              <div class="form-group mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" name="description" id="description" rows="3" 
                          placeholder="Brief description about your fraud checker API configuration">{{ $fraudApi->description ?? '' }}</textarea>
              </div>
            </div>
            <!-- col-end -->

            <div class="col-sm-6 mb-3">
              <div class="form-group">
                <label for="status" class="d-block">Enable API</label>
                <label class="switch">
                  <input type="checkbox" value="1" @if(($fraudApi->status ?? false)) checked @endif name="status" />
                  <span class="slider round"></span>
                </label>
                @error('status')
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $message }}</strong>
                </span>
                @enderror
              </div>
            </div>
            <!-- col end -->

            <div class="col-sm-12">
              <input type="submit" class="btn btn-success" value="Save Configuration" />
              <button type="button" id="testApiBtn" class="btn btn-info ms-2">Test API Connection</button>
            </div>
          </form>
        </div>
        <!-- end card-body-->
      </div>
      <!-- end card-->
    </div>
    <!-- end col-->
  </div>

</div>
@endsection 

@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-advanced.init.js"></script>

<script>
  $(document).ready(function () {
    $(".select2").select2();
    
    // Show helpful tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Test API connection
    $('#testApiBtn').click(function() {
      var btn = $(this);
      var originalText = btn.text();
      
      btn.text('Testing...').prop('disabled', true);
      
      $.ajax({
        url: '{{route('admin.fraud-checker-api.test')}}',
        method: 'POST',
        data: {
          _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          if (response.success) {
            toastr.success(response.message);
          } else {
            toastr.error(response.message);
          }
        },
        error: function(xhr) {
          toastr.error('Test failed: ' + xhr.responseJSON?.message || 'Unknown error');
        },
        complete: function() {
          btn.text(originalText).prop('disabled', false);
        }
      });
    });
  });
</script>
@endsection