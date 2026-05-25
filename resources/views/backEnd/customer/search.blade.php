@if($customers)
	<table class="table table-bordered">
		@foreach($customers as $value)
		<tr>
			<td><a href="{{route('admin.customers.profile',['id'=>$value->id])}}">{{$value->name}}
			 </a></td>
		</tr>
		@endforeach
	</table>
@endif