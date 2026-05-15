<?php

namespace App\Http\Controllers;

use App\Models\muser;
use App\Models\Userface;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;

class FaceApprovalController extends Controller
{
    protected $rekog;
    protected $collectionId;

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
        $auth = auth()->user();

        // ⛔ selain HR / Captain / Supervisor tidak boleh akses
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Anda tidak memiliki akses ke halaman ini");
        }

        $users = muser::where("fface_approved", 0)
            ->whereHas("faces")
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

    public function approve($id)
    {
        $user = muser::with("faces")->findOrFail($id);

        foreach ($user->faces as $face) {
            $filePath = public_path("hrd/biometrik/" . $face->cfilename);

            if (!file_exists($filePath)) {
                \Log::warning("Face file not found: {$filePath}");
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
                \Log::error("AWS index error: " . $e->getMessage());
            }
        }

        $user->fface_approved = 1;
        $user->save();

        return redirect()
            ->route("hr.face_approval.index")
            ->with("status", "approved");
    }

    public function reject($id)
    {
        $user = muser::with("faces")->findOrFail($id);

        foreach ($user->faces as $face) {
            $filePath = public_path("hrd/biometrik/" . $face->cfilename);

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

    public function show($id)
    {
        $auth = auth()->user();

        // 🔐 Akses terbatas
        if (!$auth->fhrd && !$auth->fadmin && !$auth->fsuper) {
            abort(403, "Tidak memiliki akses");
        }

        $user = muser::with(["faces", "department"])->findOrFail($id);

        // Jika bukan HR → batasi departemen
        if (!$auth->fhrd && $user->niddept !== $auth->niddept) {
            abort(403, "Tidak memiliki akses ke user ini");
        }

        return view("backoffice.face_show", [
            "user" => $user,
            "faces" => $user->faces,
        ]);
    }
}
