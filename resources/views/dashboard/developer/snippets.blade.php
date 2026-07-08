<!-- Language Code Snippets -->
<div class="mt-4">
    <div class="flex items-center justify-between px-4 py-2 bg-slate-900 border-t border-r border-l border-slate-800 rounded-t-lg">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500" x-text="activeLang"></span>
        <button @click="
            let txt = '';
            if (activeLang === 'CURL') txt = $refs.curl.innerText;
            else if (activeLang === 'PHP') txt = $refs.php.innerText;
            else if (activeLang === 'NODEJS') txt = $refs.nodejs.innerText;
            else if (activeLang === 'PYTHON') txt = $refs.python.innerText;
            else if (activeLang === 'JAVA') txt = $refs.java.innerText;
            else if (activeLang === 'RUBY') txt = $refs.ruby.innerText;
            navigator.clipboard.writeText(txt.trim());
            let btn = $el;
            btn.innerText = 'Copied!';
            setTimeout(() => btn.innerText = 'Copy', 1500);
        " class="text-[10px] font-semibold text-indigo-400 hover:text-indigo-300 transition-colors uppercase">
            Copy
        </button>
    </div>

    <!-- Pre blocks containing snippets -->
    <div class="bg-slate-950 border border-slate-800 rounded-b-lg p-3 text-[11px] font-mono overflow-x-auto text-slate-350 leading-relaxed">
        
        <!-- CURL -->
        <pre x-show="activeLang === 'CURL'" x-ref="curl" class="whitespace-pre-wrap break-all">curl -X {{ $method }} "{{ config('app.url') }}{{ $path }}" \
  -H "Authorization: Bearer YOUR_API_KEY" \
@if($payload)  -H "Content-Type: application/json" \
  -d '{!! $payload !!}'
@else  -H "Accept: application/json"
@endif</pre>

        <!-- PHP -->
        <pre x-show="activeLang === 'PHP'" x-ref="php" class="whitespace-pre-wrap break-all">&lt;?php

$client = new \GuzzleHttp\Client();
$response = $client->request('{{ $method }}', '{{ config('app.url') }}{{ $path }}', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Accept' => 'application/json',
    ],
@if($payload)    'json' => json_decode('{!! $payload !!}', true),
@endif
]);

echo $response->getBody();</pre>

        <!-- NODEJS -->
        <pre x-show="activeLang === 'NODEJS'" x-ref="nodejs" class="whitespace-pre-wrap break-all">fetch('{{ config('app.url') }}{{ $path }}', {
  method: '{{ $method }}',
  headers: {
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }@if($payload),
  body: JSON.stringify({!! $payload !!})@endif
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</pre>

        <!-- PYTHON -->
        <pre x-show="activeLang === 'PYTHON'" x-ref="python" class="whitespace-pre-wrap break-all">import requests

url = "{{ config('app.url') }}{{ $path }}"
headers = {
    "Authorization": "Bearer YOUR_API_KEY",
    "Content-Type": "application/json",
    "Accept": "application/json"
}
@if($payload)
data = {!! $payload !!}
response = requests.{{ strtolower($method) }}(url, json=data, headers=headers)
@else
response = requests.{{ strtolower($method) }}(url, headers=headers)
@endif

print(response.json())</pre>

        <!-- JAVA -->
        <pre x-show="activeLang === 'JAVA'" x-ref="java" class="whitespace-pre-wrap break-all">import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

HttpClient client = HttpClient.newHttpClient();
HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("{{ config('app.url') }}{{ $path }}"))
    .header("Authorization", "Bearer YOUR_API_KEY")
    .header("Content-Type", "application/json")
    .header("Accept", "application/json")
@if($method === 'POST')    .POST(HttpRequest.BodyPublishers.ofString("{!! addslashes($payload) !!}"))
@elseif($method === 'PUT')    .PUT(HttpRequest.BodyPublishers.ofString("{!! addslashes($payload) !!}"))
@elseif($method === 'DELETE')    .DELETE()
@else    .GET()
@endif    .build();

HttpResponse&lt;String&gt; response = client.send(request, HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());</pre>

        <!-- RUBY -->
        <pre x-show="activeLang === 'RUBY'" x-ref="ruby" class="whitespace-pre-wrap break-all">require 'net/http'
require 'json'
require 'uri'

uri = URI('{{ config('app.url') }}{{ $path }}')
@if($method === 'POST')
req = Net::HTTP::Post.new(uri)
@elseif($method === 'PUT')
req = Net::HTTP::Put.new(uri)
@elseif($method === 'DELETE')
req = Net::HTTP::Delete.new(uri)
@else
req = Net::HTTP::Get.new(uri)
@endif
req['Authorization'] = 'Bearer YOUR_API_KEY'
req['Content-Type'] = 'application/json'
req['Accept'] = 'application/json'
@if($payload)
req.body = {!! $payload !!}.to_json
@endif

res = Net::HTTP.start(uri.hostname, uri.port, :use_ssl => uri.scheme == 'https') do |http|
  http.request(req)
end

puts res.body</pre>

    </div>
</div>
