<?php

namespace App\Http\Controllers;

use App\Models\muser;
use App\Models\Userface;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FaceApprovalController extends Controller
{
    protected RekognitionClient $rekog;
    protected string $collectionId;

    public function __construct()
    {
        $this->rekog = new RekognitionClient([
            "region" => config("services.rekognition.region"),
            "version" => "2016-06-27",
            "credentials" => [
                "key" => config("services.rekognition.key"),
                "secret" => config("services.rekognition.secret"),
            ],
        ]);

        $this->collectionId = config("services.rekognition.collection_id");
    }

    public function index()
    {
        $auth = Auth::user();

        // ⛔ selain HR / Captain / Supervisor tidak boleh akses
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Anda tidak memiliki akses ke halaman ini");
        }

        $users = muser::where("fface_approved", 0)
            ->whereHas("faces")
            ->when(
                $auth && $auth->ccompany,
                function ($q) use ($auth) {
                    $q->where("ccompany", $auth->ccompany);
                }
            )
            ->when(
                // jika BUKAN HR → filter by departemen sendiri
                !$auth->fhrd,
                function ($q) use ($auth) {
                    $q->where("niddept", $auth->niddept);
                },
            )
            ->with(["faces", "department"])
            ->orderBy("cname")
            ->get();

        return view("faces.index", compact("users"));
    }

    public function approve(int|string $id)
    {
        $auth = Auth::user();

        // ⛔ selain HR / Captain / Supervisor tidak boleh akses
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Anda tidak memiliki akses");
        }

        $userQuery = muser::with("faces");
        if ($auth && $auth->ccompany) {
            $userQuery->where("ccompany", $auth->ccompany);
        }
        $user = $userQuery->findOrFail($id);

        if ($auth && $auth->ccompany && $user->ccompany !== $auth->ccompany) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        // Jika bukan HR → batasi departemen
        if (!$auth->fhrd && $user->niddept !== $auth->niddept) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        foreach ($user->faces as $face) {
            $filePath = public_path("karyatrahrd/biometrik/" . $face->cfilename);

            if (!file_exists($filePath)) {
                Log::warning("Face file not found: {$filePath}");
                continue;
            }

            try {
                $this->rekog->indexFaces([
                    "CollectionId" => $this->collectionId,
                    "ExternalImageId" => (string) $user->nid,
                    "Image" => [
                        "Bytes" => file_get_contents($filePath),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("AWS index error: " . $e->getMessage());
            }
        }

        $user->fface_approved = 1;
        $user->save();

        return redirect()
            ->route("hr.face_approval.index")
            ->with("status", "approved");
    }

    public function reject(int|string $id)
    {
        $auth = Auth::user();

        // ⛔ selain HR / Captain / Supervisor tidak boleh akses
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Anda tidak memiliki akses");
        }

        $userQuery = muser::with("faces");
        if ($auth && $auth->ccompany) {
            $userQuery->where("ccompany", $auth->ccompany);
        }
        $user = $userQuery->findOrFail($id);

        if ($auth && $auth->ccompany && $user->ccompany !== $auth->ccompany) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        // Jika bukan HR → batasi departemen
        if (!$auth->fhrd && $user->niddept !== $auth->niddept) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        foreach ($user->faces as $face) {
            $filePath = public_path("karyatrahrd/biometrik/" . $face->cfilename);

            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        Userface::where("nuserid", $user->nid)->delete();

        $user->fface_approved = 0;
        $user->save();

        return redirect()
            ->route("hr.face_approval.index")
            ->with("status", "rejected");
    }

    public function show(int|string $id)
    {
        $auth = Auth::user();

        // 🔐 Akses terbatas
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Tidak memiliki akses");
        }

        $userQuery = muser::with(["faces", "department"]);
        if ($auth && $auth->ccompany) {
            $userQuery->where("ccompany", $auth->ccompany);
        }
        $user = $userQuery->findOrFail($id);

        if ($auth && $auth->ccompany && $user->ccompany !== $auth->ccompany) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        // Jika bukan HR → batasi departemen
        if (!$auth->fhrd && $user->niddept !== $auth->niddept) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        return view("backoffice.face_show", [
            "user" => $user,
            "faces" => $user->faces,
        ]);
    }

    public function apiPendingList(Request $request): JsonResponse
    {
        $approver = $this->resolveApprover($request->query('approver_id'));
        if ($approver instanceof JsonResponse) {
            return $approver;
        }

        $query = muser::where('fface_approved', 0)
            ->whereHas('faces')
            ->with(['faces', 'department']);

        // Jika approver memiliki ccompany → filter ccompany
        if ($approver->ccompany) {
            $query->where('ccompany', $approver->ccompany);
        }

        // Jika bukan HRD → filter departemen sendiri
        if (!$approver->fhrd) {
            $query->where('niddept', $approver->niddept);
        }

        $users = $query->orderBy('cname')->get();

        $data = $users->map(function ($user) {
            return [
                'nid'        => $user->nid,
                'cname'      => $user->cname,
                'niddept'    => $user->niddept,
                'department' => $user->department->cname ?? null,
                'faces'      => $user->faces->map(function ($face) {
                    return [
                        'nid' => $face->nid,
                        'url' => url('karyatrahrd/biometrik/' . $face->cfilename),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function apiApprove(Request $request, int|string $id): JsonResponse
    {
        $approver = $this->resolveApprover($request->input('approver_id'));
        if ($approver instanceof JsonResponse) {
            return $approver;
        }

        $userQuery = muser::with('faces');
        if ($approver->ccompany) {
            $userQuery->where('ccompany', $approver->ccompany);
        }
        $user = $userQuery->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if ($approver->ccompany && $user->ccompany !== $approver->ccompany) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses ke user ini.'], 403);
        }

        // Jika bukan HRD → hanya boleh approve departemen sendiri
        if (!$approver->fhrd && $user->niddept !== $approver->niddept) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses ke user ini.'], 403);
        }

        $errors = [];
        foreach ($user->faces as $face) {
            $filePath = public_path('karyatrahrd/biometrik/' . $face->cfilename);

            if (!file_exists($filePath)) {
                Log::warning("API Face Approve - file not found: {$filePath}");
                $errors[] = $face->cfilename;
                continue;
            }

            try {
                $this->rekog->indexFaces([
                    'CollectionId'   => $this->collectionId,
                    'ExternalImageId' => (string) $user->nid,
                    'Image'          => [
                        'Bytes' => file_get_contents($filePath),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('API AWS index error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal index ke AWS Rekognition: ' . $e->getMessage(),
                ], 500);
            }
        }

        $user->fface_approved = 1;
        $user->save();

        return response()->json([
            'success'        => true,
            'message'        => 'Face ' . $user->cname . ' berhasil diapprove.',
            'missing_files'  => $errors,
        ]);
    }

    public function apiReject(Request $request, int|string $id): JsonResponse
    {
        $approver = $this->resolveApprover($request->input('approver_id'));
        if ($approver instanceof JsonResponse) {
            return $approver;
        }

        $userQuery = muser::with('faces');
        if ($approver->ccompany) {
            $userQuery->where('ccompany', $approver->ccompany);
        }
        $user = $userQuery->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if ($approver->ccompany && $user->ccompany !== $approver->ccompany) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses ke user ini.'], 403);
        }

        // Jika bukan HRD → hanya boleh reject departemen sendiri
        if (!$approver->fhrd && $user->niddept !== $approver->niddept) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses ke user ini.'], 403);
        }

        foreach ($user->faces as $face) {
            $filePath = public_path('karyatrahrd/biometrik/' . $face->cfilename);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        Userface::where('nuserid', $user->nid)->delete();

        $user->fface_approved = 0;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Face ' . $user->cname . ' berhasil direject dan data dihapus.',
        ]);
    }

    private function resolveApprover(int|string|null $approverId): muser|JsonResponse
    {
        if (!$approverId) {
            return response()->json(['success' => false, 'message' => 'approver_id wajib diisi.'], 422);
        }

        $approver = muser::find($approverId);
        if (!$approver) {
            return response()->json(['success' => false, 'message' => 'Approver tidak ditemukan.'], 404);
        }

        // if (!$approver->fhrd && !$approver->fadmin && !$approver->fsuper) {
        //     return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk melakukan aksi ini.'], 403);
        // }

        return $approver;
    }
}
