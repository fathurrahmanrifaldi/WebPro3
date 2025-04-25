@extends('backend.v_layouts.app')
@section('content')
<!-- contentAwal -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{$judul}} <br><br>
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Nama</th>
                            <td>{{$customer->user->nama}}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{$customer->user->email}}</td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td>{{$customer->alamat ?? '-'}}</td>
                        </tr>
                        <tr>
                            <th>Kode Pos</th>
                            <td>{{$customer->pos ?? '-'}}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($customer->user->status == 1)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                    <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- contentAkhir -->
@endsection 