<?php

namespace App\Http\Controllers\Admin;

use App\Post;
// si importa il controller poiche il PostController è dentro una cartella
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
// importo il modello Category e Tag
use App\Category;
use App\Tag;
// importo la libreria Str
use Illuminate\Support\Str;
//importo lo storage
use Illuminate\Support\Facades\Storage;
class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $posts = Post::all();
        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        // associo alla variabile tutti i valori importatida riga 11
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.create', compact(['categories','tags']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $this->validatePost($request);


        $form_data = $request->all();
        if (array_key_exists('image', $form_data)) {
            $cover_path = Storage::put('post_covers', $form_data['image']);
            $form_data['cover_path'] = $cover_path;
        }
        $post = new Post();
        $post->fill($form_data);



        $slug = $this->getSlug($post->title);
        $post->slug = $slug;

        $post->save();

        if(array_key_exists('tags', $form_data)){
            $post->tags()->sync($form_data['tags']);
        }

        return redirect()->route('admin.posts.show', $post->slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //
        return view('admin.posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.edit', compact(['post', 'categories', 'tags']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        //
        $this->validatePost($request);
        $form_data = $request->all();
         //qui aggiorniamo lo slug se il titolo è diverso
        if ($post->title != $form_data['title']) {
            $slug = $this->getSlug($form_data['title']);
            $form_data['slug'] = $slug;
        }

        if(array_key_exists('tags', $form_data)){
            $post->tags()->sync($form_data['tags']);
        }else{
            $post->tags()->sync([]);
        }
        if (array_key_exists('image', $form_data)) {
            if ($post->cover_path) {
                Storage::delete($post->cover_path);
            }
            $cover_path = Storage::put('post_covers', $form_data['image']);
            $form_data['cover_path'] = $cover_path;
        }
        $post->update($form_data);
        return redirect()->route('admin.posts.show', $post->slug);
        // ADRIANO è STATO COSTRETTO A FARE COSì PERCHè NON GLI AGGIORNAVA LO SLUG // perchè la latenza è troppa
        // $slug = $post->slug;
        // return redirect()->route('admin.posts.show', $slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {   //elimino eventualmente l'immagine
        if ($post->cover_path) {
            Storage::delete($post->cover_path);
        }
        //eliminiamo le relazioni dei tags con il post
        $post->tags()->sync([]);
        //eliminiamo ora il post
        $post->delete();
        return redirect()->route('admin.posts.index');
    }

    private function getSlug($title)
    {
        $slug = Str::slug($title);
        $slug_base = $slug;

        $existingPost = Post::where('slug', $slug)->first();
        $counter = 1;
        while ($existingPost) {
            $slug = $slug_base . '_' . $counter;
            $counter++;
            $existingPost = Post::where('slug', $slug)->first();
        }
        return $slug;
    }
    private function validatePost(Request $request){
        $request->validate([
            'title' => 'required|min:5|max:255',
            'content' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'exists:tags,id',
            'image' => 'nullable|image|max:3072'
        ], [
            'category_id.exists' => 'non esiste :('
        ]);
    }
}
