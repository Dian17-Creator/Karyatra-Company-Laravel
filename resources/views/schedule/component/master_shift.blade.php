 <div class="card mb-4">
     <div class="card-header bg-dark text-white">Daftar Shift</div>
     <div class="card-body">
         <table class="table table-bordered align-middle text-center">
             <thead class="table-success">
                 <tr>
                     <th>Nama Shift</th>
                     <th>Total Jam Kerja</th>
                     <th>Mulai</th>
                     <th>Selesai</th>
                     <th>Mulai (Split)</th>
                     <th>Selesai (Split)</th>
                     <th>Aksi</th>
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


                     {{-- Modal Edit Shift --}}
                     @include('schedule.modal.modal_edit_shift')
                 @empty
                     <tr>
                         <td colspan="4" class="text-muted">Belum ada data shift</td>
                     </tr>
                 @endforelse
             </tbody>
         </table>
     </div>
 </div>
