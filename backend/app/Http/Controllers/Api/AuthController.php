<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'          => 'required|string',
                'bio'           => 'required|string',
                'username'      => 'required|string',
                'password'      => 'required|string',
                'is_private'    => 'boolean',
            ]);

            $userId = DB::table('users')->insertGetId([
                'name'          => $validated['name'],
                'bio'           => $validated['bio'],
                'username'      => $validated['username'],
                'password'      => Hash::make($validated['password']),
                'is_private'    => $validated['is_private'] ?? false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $user = \App\Models\User::find($userId);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'register succes',
                'token'   => $token,
                'user'    => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'bio'       => $user->bio,
                    'username'  => $user->username,
                    'is_private'=> $user->is_private,
                ]    
            ], 201);
        } catch (\Exceotion $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'akun sudah terdaftar',
                'error'     =>  $e->getmessage()
            ], 500);
        }
        
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
            'username'  => 'required|string',
            'password'  => 'required|string',
        ]);
        $user = \App\Models\User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'   => 'login success',
            'token'     => $token,
            'user'      => [
                'id'        => $user->id,
                'name'      => $user->name,
                'bio'       => $user->bio,
                'username'  => $user->username,
                'is_private'=> $user->is_private,
            ]
        ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'succes'     =>     false,
                'message'   =>     'password anda salah',
                'error'     =>      $e->getmessage()
            ], 500);
        }
        
    }

    public function private(Request $request)
    {
        try {
            $request->validate([
            'is_private' => 'required|boolean'
        ]);

        $userId = auth()->id();

        DB::table('users')
            ->where('id', $userId)
            ->update([
                'is_private' => $request->is_private,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => $request->is_private
                ? 'Akun sekarang private'
                : 'Akun sekarang public',
            'is_private' => (bool)$request->is_private
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal',
                'error'     =>  $get->getmessage()
            ], 500);
        }
        
    }

    public function showUser()
    {
        try {
            $users = DB::table('users')
            ->select('name', 'bio', 'username')
            ->get();

        return response()->json([
            'message' => 'Get users success',
            'total'   => $users->count(),
            'users'   => $users
        ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal acces',
                'error'     =>  $getmessage()
            ], 500);
        }
        
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

    // jumlah post
    $totalPosts = DB::table('posts')
        ->where('user_id', $user->id)
        ->whereNull('deleted_at')
        ->count();

    // jumlah followers (yang follow dia dan sudah accepted)
    $followers = DB::table('follows')
        ->where('followed_id', $user->id)
        ->where('accepted', 1)
        ->count();

    // jumlah following (yang dia follow dan sudah accepted)
    $following = DB::table('follows')
        ->where('follower_id', $user->id)
        ->where('accepted', 1)
        ->count();

    return response()->json([
        'user' => [
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->username,
            'bio'         => $user->bio,
            'is_private'  => $user->is_private,

            // statistik
            'total_posts'=> $totalPosts,
            'followers'  => $followers,
            'following'  => $following,
        ]
    ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal menampilkan profile',
                'error'     =>  $e->getmessage()
            ], 500);
        }
    }


    public function posts(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
            'caption' => 'required|string',
            'images.*' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);
            // 1. Insert ke posts
            $postId = DB::table('posts')->insertGetId([
                'user_id' => auth()->id(),
                'caption' => $request->caption,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insert ke attachments
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $path = $image->store('posts', 'public');

                    DB::table('attachments')->insert([
                        'post_id' => $postId,
                        'storage_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post berhasil dibuat',
                'post_id' => $postId
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function follows(Request $request)
    {
        try {
            $request->validate([
            'followed_id' => 'required|exists:users,id'
        ]);

        $followerId = auth()->id();
        $followedId = $request->followed_id;

        // ❌ Tidak boleh follow diri sendiri
        if ($followerId == $followedId) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa follow diri sendiri'
            ], 400);
        }

        // 🔎 Cek apakah sudah follow
        $already = DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah follow user ini'
            ], 409);
        }

        // 🔎 Cek apakah akun target private
        $isPrivate = DB::table('users')
            ->where('id', $followedId)
            ->value('is_private'); // 0 / 1

        // Kalau public → auto accepted
        $accepted = $isPrivate == 0 ? 1 : 0;

        // ➕ Insert ke follows
        DB::table('follows')->insert([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
            'accepted' => $accepted,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $accepted 
                ? 'Berhasil follow user'
                : 'Permintaan follow dikirim',
            'accepted' => $accepted
        ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal mengikuti user',
                'error'     =>  $e->getmessage()
            ], 500);
        }
        
    }

    public function accept(Request $request)
    {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
        $request->validate([
            'follower_id' => 'required|exists:users,id'
        ]);

        $followedId = auth()->id();
        $followerId = $request->follower_id;

        // 🔎 Cek apakah ada request follow pending
        $follow = DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->where('accepted', 0)
            ->first();

        if (!$follow) {
            return response()->json([
                'success' => false,
                'message' => 'Request tidak ditemukan atau sudah diterima'
            ], 404);
        }

        // ✅ Update jadi accepted
        DB::table('follows')
            ->where('id', $follow->id)
            ->update([
                'accepted' => 1,
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Follow berhasil diterima'
        ]);
    }

    public function hp(Request $request)
    {
        try {
            $authId = auth()->id();

    $posts = DB::table('posts')
        ->join('users', 'posts.user_id', '=', 'users.id')

        // relasi follow viewer -> pemilik post
        ->leftJoin('follows', function ($join) use ($authId) {
            $join->on('follows.followed_id', '=', 'users.id')
                 ->where('follows.follower_id', '=', $authId);
        })

        ->leftJoin('attachments', 'attachments.post_id', '=', 'posts.id')

        // ❗ filter soft delete
        ->whereNull('posts.deleted_at')

        ->where(function ($query) {
            $query->where('users.is_private', 0)
                  ->orWhere(function ($q) {
                      $q->where('users.is_private', 1)
                        ->where('follows.accepted', 1);
                  });
        })

        ->select(
            'posts.id',
            'posts.caption',
            'posts.created_at',
            'users.id as user_id',
            'users.name',
            'users.username',
            'attachments.storage_path'
        )
        ->orderBy('posts.created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $posts
    ]);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal menampilkan halaman',
                'error'     =>  $e->getmessage()
            ], 500);
        }
    
    }


    public function delete($id)
    {
        try {
            $authId = auth()->id();

        $post = DB::table('posts')->where('id', $id)->first();

        // ❌ Post tidak ditemukan
        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        // ❌ Bukan pemilik post
        if ($post->user_id !== $authId) {
            return response()->json([
                'message' => 'Forbidden access'
            ], 403);
        }

        // ✅ Soft delete
        DB::table('posts')
            ->where('id', $id)
            ->update([
                'deleted_at' => now()
            ]);

        // HTTP 204 = No Content
        return response()->json([
    'message' => 'Post berhasil terhapus'
], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'gagal menghapus postingan',
                'error'     =>  $e->getmessage()
            ], 500);
        }
    }
}
