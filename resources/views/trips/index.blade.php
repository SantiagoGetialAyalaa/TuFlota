<h1>Buscar viajes</h1>

<form method="GET" action="/trips">
    <input type="text" name="origin" placeholder="Origen">
    <input type="text" name="destination" placeholder="Destino">
    <button type="submit">Buscar</button>
</form>

<hr>

@if(count($trips) > 0)

    @foreach($trips as $trip)
        <div style="border:1px solid black; padding:10px; margin:10px;">
            <p>
                {{ $trip->schedule->route->origin }} →
                {{ $trip->schedule->route->destination }}
            </p>

            <p>
                Hora: {{ $trip->schedule->departure_time }}
            </p>
        </div>
    @endforeach

@else
    <p>No hay viajes</p>
@endif