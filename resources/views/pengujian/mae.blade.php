@extends('layouts.app')

@section('content')
<div class="container mt-5 py-4">
    <h3>Pengujian Sistem - Mean Absolute Error (MAE)</h3>

    @if($mae !== null)
        <p>Total Data Diuji: <strong>{{ $jumlahData }}</strong></p>
        <p>Hasil MAE: <strong>{{ number_format($mae, 4) }}</strong> ({{ number_format($mae * 100, 2) }}%)</p>

        {{-- Keterangan Akurasi --}}
        @php
            $keterangan = '';
            if ($mae <= 0.1) {
                $keterangan = 'Sistem sangat akurat';
            } elseif ($mae <= 0.2) {
                $keterangan = 'Sistem akurat';
            } elseif ($mae <= 0.3) {
                $keterangan = 'Sistem cukup akurat';
            } elseif ($mae <= 0.4) {
                $keterangan = 'Sistem kurang akurat';
            } else {
                $keterangan = 'Sistem tidak akurat';
            }
        @endphp

        <div class="alert alert-info mt-3">
            <strong>Keterangan:</strong> {{ $keterangan }}
        </div>
        
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Kos</th>
                    <th>Prediksi (0-1)</th>
                    <th>Skor User (1-5)</th>
                    <th>Skor aktual (0-1)</th>
                    <th>|Prediksi - Aktual|</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detail as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row['id_kos'] }}</td>
                        <td>{{ $row['prediksi'] }}</td>
                        <td>{{ $row['aktual'] }}</td>
                        <td>{{ $row['normal_aktual'] }}</td>
                        <td>{{ $row['selisih'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>Belum ada data survey untuk dilakukan pengujian.</em></p>
    @endif
</div>
@endsection
