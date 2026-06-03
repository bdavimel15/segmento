@if(isset($cliente) && $cliente->exists)
    @include('clientes.edit')
@else
    @include('clientes.create')
@endif
