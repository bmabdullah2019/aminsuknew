@extends('backEnd.layouts.master')
@section('title','Edit Warehouse')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.warehouse.index')}}" class="btn btn-danger rounded-pill"><i class="fe-arrow-left"></i> Back</a>
                </div>
                <h4 class="page-title">Edit Warehouse</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{route('admin.warehouse.update',$warehouse->id)}}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Warehouse Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{old('code',$warehouse->code)}}" required>
                                @error('code')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Warehouse Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{old('name',$warehouse->name)}}" required>
                                @error('name')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="main" {{old('type',$warehouse->type)=='main'?'selected':''}}>Main Warehouse</option>
                                    <option value="branch" {{old('type',$warehouse->type)=='branch'?'selected':''}}>Branch Warehouse</option>
                                    <option value="virtual" {{old('type',$warehouse->type)=='virtual'?'selected':''}}>Virtual Warehouse</option>
                                    <option value="transit" {{old('type',$warehouse->type)=='transit'?'selected':''}}>Transit Warehouse</option>
                                </select>
                                @error('type')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Manager</label>
                                <select name="manager_id" class="form-control">
                                    <option value="">Select Manager</option>
                                    @foreach($managers as $user)
                                        <option value="{{$user->id}}" {{old('manager_id',$warehouse->manager_id)==$user->id?'selected':''}}>{{$user->name}}</option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="2">{{old('address',$warehouse->address)}}</textarea>
                                @error('address')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" value="{{old('city',$warehouse->city)}}">
                                @error('city')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>State</label>
                                <input type="text" name="state" class="form-control" value="{{old('state',$warehouse->state)}}">
                                @error('state')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Country</label>
                                <input type="text" name="country" class="form-control" value="{{old('country',$warehouse->country)}}">
                                @error('country')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="{{old('postal_code',$warehouse->postal_code)}}">
                                @error('postal_code')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{old('phone',$warehouse->phone)}}">
                                @error('phone')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{old('email',$warehouse->email)}}">
                                @error('email')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Capacity (sqft)</label>
                                <input type="number" name="capacity_sqft" class="form-control" value="{{old('capacity_sqft',$warehouse->capacity_sqft)}}" step="0.01" min="0">
                                @error('capacity_sqft')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Opening Date</label>
                                <input type="date" name="opening_date" class="form-control" value="{{old('opening_date',$warehouse->opening_date?$warehouse->opening_date->format('Y-m-d'):'')}}">
                                @error('opening_date')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Status</label>
                                <select name="is_active" class="form-control">
                                    <option value="1" {{old('is_active',$warehouse->is_active)==1?'selected':''}}>Active</option>
                                    <option value="0" {{old('is_active',$warehouse->is_active)==0?'selected':''}}>Inactive</option>
                                </select>
                                @error('is_active')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{old('notes',$warehouse->notes)}}</textarea>
                                @error('notes')
                                    <span class="text-danger">{{$message}}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <button type="submit" class="btn btn-primary">Update Warehouse</button>
                                <a href="{{route('admin.warehouse.index')}}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

