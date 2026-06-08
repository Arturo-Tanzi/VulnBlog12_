<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;


class UserController extends Controller
{
    // UNSECURE
    // public function show($id)
	// {
	// 	$user = User::findOrFail($id);

    //     return view('auth.profile',compact('user'));
	// }

    // SECURE
    public function profile(){
        if(!$user = Auth::user()) //Se l'utente non è autenticato, ritorna un errore 403
        return response()->json(['message' => 'Forbidden Operation'], 403);
        
        return view('auth.profile',compact('user')); //Ritorna la vista del profilo con i dati dell'utente autenticato
    }

    public function update(Request $request, $id){
        $user = User::find($id);

        // Faccio la VALIDAZIONE dei dati in ingresso
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);
         $user->update($request->all());


        return back()->with('message','User updated');
    }
    public function changeEmail(Request $request){
        
        if(!$user = Auth::user())
        return response()->json(['message' => 'Forbidden Operation'], 403);
        
        $user->email = $request->email;
        $user->save();
        
        return back()->with('message','Changed successfully');
    }
    
    public function changeName(Request $request)
    {
        if(!$user = Auth::user())
        return response()->json(['message' => 'Forbidden Operation'], 403);
        
        $user->name = $request->name;
        $user->save();
        
        return back()->with('message','Changed successfully');
    }
    
    public function changeImg(Request $request)
    {
        if(!$user = Auth::user()){
            return back()->with('message','Please Log In');
        }
        
        if(!$request->hasFile('avatar')) {
            return back()->with('message','Forbidden Operation');
        }
        
        if (!file_exists(storage_path("app/public/images/users/".$user->id))) {
            mkdir(storage_path("app/public/images/users/".$user->id), 0777, true);
        }

        // retrieve uploaded image
        $newImage = $request->file('avatar');
        // calculate hash

        // UNSECURE with md5
        //$newImageHash = md5_file($newImage);

        // SECURE with sha256 (algoritmo più sicuro e meno vulnerabile a collisioni rispetto a md5)
        //Per evitare che un attaccante possa calcolare il hash di un file malevolo con lo stesso hash di un file legittimo, è possibile utilizzare un algoritmo di hashing più sicuro come SHA-256, che produce un hash più lungo e complesso, rendendo molto più difficile per un attaccante trovare due file diversi con lo stesso hash (collisione).
        // sha1 è considerato insicuro e vulnerabile a collisioni, quindi non è consigliato
        // ? sha256 è attualmente considerato sicuro e resistente alle collisioni, quindi è una scelta migliore rispetto a md5 e sha1 per calcolare l'hash di un file.
        // sha512 è ancora più sicuro di sha256, ma è più lento da calcolare, quindi potrebbe non essere necessario per la maggior parte delle applicazioni. 
        // sha384 è ancora più sicuro di sha512, ma è ancora più lento da calcolare, quindi potrebbe essere eccessivo per la maggior parte delle applicazioni.
        $newImageHash = hash_file('sha256', $newImage);
    
        // compare hash
        if($newImageHash == $user->avatar){
            return redirect()->back()->with('message','Image not updated, same');
        }
        // Define the path to store the image
        $path = "images/users/".$user->id;

       
        Storage::deleteDirectory($path);
    
        
        // Store the image in the defined path
        $filePath = $newImage->storeAs($path, $newImageHash, 'public');
    
        // save new user avatar name
        $user->avatar = $newImageHash;
        $user->save();

        return redirect()->back()->with('message','Image updated');
    }

    public function download(Request $request) {
    // SECURE
    // $filename = $request->get('filename');
    // if (!$file_exists = (storage_path('app/public/'.$filename))) {
    //     return back()->with('message','Filename NOT FOUND');
    // }
    return Storage::disk('local')->download($request->get('filename'));

    //UNSECURE
        return response()->download(storage_path('app/private/'.$request->get('filename')));
    }
    public function upload(Request $request) {
        
    // UNSECURE
        // if(!$user = Auth::user()){ {
        //     return back()->with('message','Please Log In');
        // }
        // if(!$request->hasFile('file')) {
        //     return back()->with('message','Forbidden Operation');
        // }
        // $path = storage_path('app/public/docs/users/'.$user->id);
        // if (!file_exists($path)) {
        //     mkdir($path, 0777, true);
        // }
        // $file = $request->file('file');
    
        // //UNSECURE
        // $fileName = $file->getClientOriginalName();
    
        // $file->move($path, $fileName);
    
        // File::create([
        //     'name' => $fileName,
        //     'user_id' => $user->id,
        // ]);
        
        // return back()->with('message','File uploaded');
    
        // SECURE
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Estensione consentite
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // MIME type consentiti
    
        $file = $request->file('file');
        $extensione = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
    
        if (!in_array($extensione, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
            return back()->withErrors(['File type not allowed.']);
        }
        $filename = $file->getClientOriginalName();
        $fileuid = uniqid(). '.' . $extensione; // Genera un nome univoco per il file
        $path = $file->storeAs("docs/users/{$user->id}", $fileuid, 'local'); // Salva il file con il nome univoco
        File::create([
            'name' => $filename,
            'uid' => $fileuid,
            'user_id' => $user->id,
        ]);
        return back()->with('message','File uploaded');
    }

    public function downloadPrivateFile(Request $request) 
    {
        if (!$user = Auth::user()) {
            return back()->with('message','Please Log In');
        }

        $fileRecord = File::where('uid', $file)->where('user_id', $user->id)->firstOrFail();

        if (!$fileRecord) {
            return back()->with('message','File not found');
        }

        $path = "docs/users/{$user->id}/{$fileRecord->uid}";

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $fileRecord->uid);
    }

}