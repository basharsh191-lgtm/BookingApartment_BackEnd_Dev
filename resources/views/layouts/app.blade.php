{{-- @extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h2 class="mb-4 text-center">إدارة طلبات التسجيل</h2>

    @if(session('success'))
        <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger text-center">{{ session('error') }}</div>
    @endif

    @if($users->count() == 0)
        <div class="alert alert-info text-center">
            لا يوجد مستخدمون بانتظار الموافقة حالياً.
        </div>
    @else

    <table class="table table-bordered table-hover text-center">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>الاسم الكامل</th>
                <th>رقم الهاتف</th>
                <th>نوع المستخدم</th>
                <th>تاريخ الميلاد</th>
                <th>الهوية</th>
                <th>الصورة الشخصية</th>
                <th>التحكم</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                <td>{{ $user->phone_number }}</td>
                <td>{{ $user->user_type }}</td>
                <td>{{ $user->birth_date }}</td>

                <!-- صورة الهوية -->
                <td>
                    <img src="{{ asset('uploads/id_cards/'.$user->id_card_image) }}"
                        width="80" style="border-radius:5px;">
                </td>

                <!-- الصورة الشخصية -->
                <td>
                    <img src="{{ asset('uploads/profiles/'.$user->profile_image) }}"
                        width="80" style="border-radius:50%;">
                </td>

                <td>
                    <form action="{{ route('admin.approve', $user->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        <button class="btn btn-success btn-sm">قبول</button>
                    </form>

                    <form action="{{ route('admin.reject', $user->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        <button class="btn btn-danger btn-sm">رفض</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @endif
</div>
@endsection --}}
