 <div class="card mb-4">
     <div class="card-header bg-danger text-white">Daftar Shift</div>
     <div class="card-body">
         <table class="table table-bordered align-middle text-center">
             <thead class="table-success">
                 <tr>
                     <th style="background-color: #ffd8e0ff !important;">Nama Shift</th>
                     <th style="background-color: #ffd8e0ff !important;">Total Jam Kerja</th>
                     <th style="background-color: #ffd8e0ff !important;">Mulai</th>
                     <th style="background-color: #ffd8e0ff !important;">Selesai</th>
                     <th style="background-color: #ffd8e0ff !important;">Mulai (Split)</th>
                     <th style="background-color: #ffd8e0ff !important;">Selesai (Split)</th>
                     <th style="background-color: #ffd8e0ff !important;">Aksi</th>
                 </tr>
             </thead>
             <tbody>
                 @forelse($masters as $shift)
                 <tr>
                     <td>{{ $shift->cname }}</td>

                     {{-- TOTAL JAM KERJA --}}
                     <td>
                         {{ $shift->ctotal ? $shift->ctotal . ' Jam' : '-' }}
                     </td>

                     {{-- JAM MULAI --}}
                     <td>
                         {{ $shift->dstart ? substr($shift->dstart, 0, 5) : '-' }}
                     </td>

                     {{-- JAM SELESAI --}}
                     <td>
                         {{ $shift->dend ? substr($shift->dend, 0, 5) : '-' }}
                     </td>

                     <td>
                         {{ $shift->dstart2 ? substr($shift->dstart2, 0, 5) : '-' }}
                     </td>

                     <td>
                         {{ $shift->dend2 ? substr($shift->dend2, 0, 5) : '-' }}
                     </td>

                     <td>
                         <div class="d-flex justify-content-center gap-2">
                             <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal"
                                 data-bs-target="#editShiftModal{{ $shift->nid }}">
                                 Edit
                             </button>

                             <form action="{{ url('/schedule/' . $shift->nid) }}" method="POST"
                                 onsubmit="return confirm('Hapus shift ini?')">
                                 @csrf
                                 @method('DELETE')
                                 <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                             </form>
                         </div>
                     </td>
                 </tr>
                 @empty
                 <tr>
                     <td colspan="7" class="text-muted">Belum ada data shift</td>
                 </tr>
                 @endforelse
             </tbody>
         </table>
     </div>
 </div>

 {{-- Modal Edit Shift rendered safely outside the table --}}
 @foreach($masters as $shift)
 @include('schedule.modal.modal_edit_shift')
 @endforeach