<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

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
                    'is_private' => $user->is_private,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'succes'    =>  false,
                'message'   =>  'akun sudah terdaftar',
                'error'     =>  $e->getMessage()
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
                    'is_private' => $user->is_private,
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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validated = $request->validate([
                'is_private' => 'required|boolean'
            ]);

            $userId = $user->id;

            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'is_private' => $validated['is_private'],
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
                'error'     =>  $e->getMessage()
            ], 500);
        }
    }

    public function showUser($username)
    {
        try {
            $users = DB::table('users')
                ->where('username', $username)
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
                'error'     =>  $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            // pastikan user
            $user = $request->user();

            // jumlah post
            $totalPosts = DB::table('posts')
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->count();

            $followers = DB::table('follows')
                ->where('followed_id', $user->id)
                ->where('accepted', 1)
                ->count();

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
                    'total_posts' => $totalPosts,
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
        try {
            // pastikan user login
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validated = $request->validate([
                'caption'   => 'required|string',
                'images.*'  => 'required|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            // 1. Insert ke posts
            $postId = DB::table('posts')->insertGetId([
                'user_id'    => $user->id,
                'caption'    => $validated['caption'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insert ke attachments
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $path = $image->store('posts', 'public');
                    DB::table('attachments')->insert([
                        'post_id'     => $postId,
                        'storage_path' => $path,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Post berhasil dibuat',
                'post_id' => $postId
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat post',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function follows(Request $request)
    {
        try {
            $validated = $request->validate([
                'followed_id' => 'required|exists:users,id'
            ]);

            // ambil user login
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $followerId = $user->id;
            $followedId = $validated['followed_id'];

            // tidak boleh follow diri sendiri
            if ($followerId == $followedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa follow diri sendiri'
                ], 400);
            }

            // cek sudah follow
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

            // cek private
            $isPrivate = DB::table('users')
                ->where('id', $followedId)
                ->value('is_private');

            $accepted = $isPrivate == 0 ? 1 : 0;

            DB::table('follows')->insert([
                'follower_id' => $followerId,
                'followed_id' => $followedId,
                'accepted'    => $accepted,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json([
                'success'  => true,
                'message'  => $accepted
                    ? 'Berhasil follow user'
                    : 'Permintaan follow dikirim',
                'accepted' => $accepted
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengikuti user',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function accept(Request $request)
    {
        try {

            $validated = $request->validate([
                'follower_id' => 'required|exists:users,id'
            ]);

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $followedId = $user->id;
            $followerId = $validated['follower_id'];

            // cek follow request pending
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

            // update jadi accepted
            DB::table('follows')
                ->where('id', $follow->id)
                ->update([
                    'accepted'   => 1,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Follow berhasil diterima'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function hp(Request $request)
    {
        try {
            $user = $request->user();
            $authId = $user ? $user->id : null;

            $followed = DB::table('follows')
                ->where('follower_id', $authId)
                ->where('accepted', 1)
                ->get();

            $posts = DB::table('posts')
                ->leftJoin('users', 'users.id', '=', 'posts.user_id')
                ->whereIn('users.id', [$authId, ...$followed->pluck('followed_id')])
                ->select(
                    'posts.*',
                    'users.id as user_id',
                    'users.name',
                    'users.username',
                )
                ->get();

            $attachments = DB::table('attachments')
                ->whereIn('attachments.post_id', $posts->pluck('id'))
                ->get();

            $posts_ready = $posts
                ->map(function ($value) use ($attachments) {
                    $value->attachments = $attachments->where('post_id', $value->id);
                    return $value;
                });

            return response()->json([
                'success' => true,
                'data' => $posts_ready
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menampilkan halaman',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function delete(Request $request, $id)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $authId = $user->id;

            $post = DB::table('posts')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            // post tidak ditemukan
            if (!$post) {
                return response()->json([
                    'message' => 'Post not found'
                ], 404);
            }

            // bukan pemilik post
            if ($post->user_id != $authId) {
                return response()->json([
                    'message' => 'Forbidden access'
                ], 403);
            }

            // soft delete
            DB::table('posts')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Post berhasil terhapus'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus postingan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
