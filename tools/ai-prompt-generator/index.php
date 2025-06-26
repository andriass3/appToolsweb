<?php
$page_title = "AI Prompt Generator";
$path_prefix = '../../'; // Sesuaikan jika struktur folder Anda berbeda
// Meta tag untuk statistik penggunaan tool (jika Anda mengimplementasikannya)
$tool_slug_for_stats = "ai-prompt-generator"; 
include $path_prefix . 'header.php';
?>

<style>
    /* Tambahkan sedikit style untuk transisi buka-tutup form */
    .options-container {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-in-out;
    }
    .options-container.visible {
        max-height: 1500px; /* Sesuaikan jika form lebih tinggi */
    }
    .manual-input-container {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
        margin-top: 0.5rem; /* Sedikit jarak */
    }
    .manual-input-container.visible {
        max-height: 200px; /* Cukup untuk input dan label kecil */
    }
</style>

<div class='tool-page-container'>
    <div class="text-center mb-4">
        <h1><i class="fas fa-lightbulb me-2"></i><?php echo $page_title; ?></h1>
        <p class="lead text-muted">Buat prompt deskriptif untuk AI image/video generator.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="alert alert-info" role="alert">
                <strong>Info:</strong> Untuk hasil video terbaik dengan model seperti Veo, berikan deskripsi yang detail mengenai adegan, subjek, aksi, gerakan kamera, dan gaya visual.
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="promptType" class="form-label fs-5">Tipe Prompt:</label>
                    <select id="promptType" class="form-select form-select-lg">
                        <option value="image">Gambar</option>
                        <option value="video" selected>Video (Optimal untuk Veo)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="outputLanguage" class="form-label fs-5">Bahasa Output Prompt:</label>
                    <select id="outputLanguage" class="form-select form-select-lg">
                        <option value="English">English (Recommended for Veo)</option>
                        <option value="Indonesian">Bahasa Indonesia</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="baseIdea" class="form-label fs-5">Ide Dasar / Subjek Utama Prompt:</label>
                <textarea id="baseIdea" class="form-control form-control-lg" rows="3" placeholder="Contoh: seekor elang terbang di atas pegunungan bersalju, seorang wanita menari di tengah hujan meteor"></textarea>
            </div>

            <div id="imageOptionsContainer" class="options-container">
                <h5 class="mt-4 mb-3 border-bottom pb-2">Opsi Tambahan untuk Gambar</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="imageSize" class="form-label">Ukuran/Rasio Aspek:</label>
                        <select id="imageSize" class="form-select">
                            <option value="square (1:1)">Persegi (1:1)</option>
                            <option value="portrait (2:3)">Potret (2:3)</option>
                            <option value="landscape (16:9)">Lanskap (16:9)</option>
                            <option value="ultrawide (21:9)">Ultrawide (21:9)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="imageStyle" class="form-label">Gaya Visual:</label>
                        <select id="imageStyle" class="form-select">
                            <option value="realistic">Realistis</option>
                            <option value="photorealistic">Fotorealistis</option>
                            <option value="cartoon">Kartun</option>
                            <option value="anime">Anime</option>
                            <option value="fantasy art">Seni Fantasi</option>
                            <option value="sci-fi art">Seni Sci-Fi</option>
                            <option value="impressionistic">Impresionistik</option>
                            <option value="surreal">Surealis</option>
                            <option value="pixel art">Pixel Art</option>
                            <option value="low poly">Low Poly</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="imageMedium" class="form-label">Medium Artistik:</label>
                        <select id="imageMedium" class="form-select">
                            <option value="digital painting">Lukisan Digital</option>
                            <option value="oil painting">Lukisan Cat Minyak</option>
                            <option value="watercolor">Cat Air</option>
                            <option value="photograph">Fotografi</option>
                            <option value="3D render">Render 3D</option>
                            <option value="pencil sketch">Sketsa Pensil</option>
                            <option value="concept art">Concept Art</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="imageLighting" class="form-label">Pencahayaan:</label>
                        <select id="imageLighting" class="form-select">
                            <option value="cinematic lighting">Pencahayaan Sinematik</option>
                            <option value="studio lighting">Pencahayaan Studio</option>
                            <option value="natural light">Cahaya Alami</option>
                            <option value="dramatic lighting">Pencahayaan Dramatis</option>
                            <option value="soft lighting">Pencahayaan Lembut</option>
                            <option value="rim lighting">Rim Lighting</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="imageColors" class="form-label">Skema Warna:</label>
                        <select id="imageColors" class="form-select">
                            <option value="vibrant colors">Warna Cerah</option>
                            <option value="monochrome">Monokrom</option>
                            <option value="pastel colors">Warna Pastel</option>
                            <option value="warm colors">Warna Hangat</option>
                            <option value="cool colors">Warna Dingin</option>
                            <option value="neon colors">Warna Neon</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="videoOptionsContainer" class="options-container">
                <h5 class="mt-4 mb-3 border-bottom pb-2">Opsi Tambahan untuk Video (Veo)</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="videoAspectRatio" class="form-label">Rasio Aspek Video:</label>
                        <select id="videoAspectRatio" class="form-select">
                            <option value="16:9 (landscape)">16:9 (Lanskap)</option>
                            <option value="9:16 (portrait/vertical)">9:16 (Potret/Vertikal)</option>
                            <option value="1:1 (square)">1:1 (Persegi)</option>
                            <option value="4:3 (standard)">4:3 (Standar)</option>
                            <option value="21:9 (cinematic widescreen)">21:9 (Layar Lebar Sinematik)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="videoVisualStyle" class="form-label">Gaya Visual Video:</label>
                        <select id="videoVisualStyle" class="form-select">
                            <option value="cinematic and photorealistic">Sinematik & Fotorealistis</option>
                            <option value="hyperrealistic">Hyperrealistis</option>
                            <option value="anime style">Gaya Anime</option>
                            <option value="claymation">Claymation</option>
                            <option value="documentary footage">Rekaman Dokumenter</option>
                            <option value="vintage film look">Tampilan Film Vintage</option>
                            <option value="dreamlike and surreal">Seperti Mimpi & Surealis</option>
                            <option value="time-lapse">Time-lapse</option>
                            <option value="slow motion footage">Rekaman Slow Motion</option>
                            <option value="found footage style">Gaya Found Footage</option>
                            <option value="epic fantasy style">Gaya Fantasi Epik</option>
                            <option value="sci-fi concept art style">Gaya Concept Art Sci-Fi</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="videoCameraMovementSelect" class="form-label">Gerakan Kamera:</label>
                        <select id="videoCameraMovementSelect" class="form-select">
                            <option value="">Tidak Ada Gerakan Spesifik</option>
                            <option value="panning shot (left to right / right to left)">Panning Shot</option>
                            <option value="tilting shot (up / down)">Tilting Shot</option>
                            <option value="zoom in (slowly / quickly)">Zoom In</option>
                            <option value="zoom out (slowly / quickly)">Zoom Out</option>
                            <option value="dolly shot (towards / away from subject)">Dolly Shot</option>
                            <option value="tracking shot / follow shot (following subject)">Tracking/Follow Shot</option>
                            <option value="crane shot / jib shot (high angle looking down / low angle looking up)">Crane/Jib Shot</option>
                            <option value="drone footage / aerial view">Drone Footage / Aerial View</option>
                            <option value="handheld camera (stable / shaky)">Handheld Camera</option>
                            <option value="first-person view (FPV)">First-Person View (FPV)</option>
                            <option value="orbit shot (circling the subject)">Orbit Shot</option>
                            <option value="static shot">Static Shot (Tidak Bergerak)</option>
                            <option value="other">Lainnya (Isi Manual)</option>
                        </select>
                        <div id="manualCameraMovementContainer" class="manual-input-container mt-2">
                            <input type="text" id="videoCameraMovementManual" class="form-control" placeholder="Deskripsikan gerakan kamera manual...">
                            <small class="form-text text-muted">Pisahkan beberapa gerakan dengan koma jika perlu.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="videoMood" class="form-label">Mood/Suasana Video:</label>
                        <select id="videoMood" class="form-select">
                            <option value="">Tidak ditentukan</option>
                            <option value="epic and grand">Epik & Megah</option>
                            <option value="serene and peaceful">Tenang & Damai</option>
                            <option value="mysterious and suspenseful">Misterius & Mencekam</option>
                            <option value="joyful and uplifting">Gembira & Membangkitkan Semangat</option>
                            <option value="dramatic and intense">Dramatis & Intens</option>
                            <option value="comedic and lighthearted">Komedis & Ringan</option>
                            <option value="nostalgic and melancholic">Nostalgia & Melankolis</option>
                            <option value="futuristic and sleek">Futuristik & Ramping</option>
                            <option value="chaotic and energetic">Kacau & Enerjik</option>
                        </select>
                    </div>
                </div>

                 <div class="row g-3 mt-1">
                    <div class="col-md-12">
                        <label for="videoSceneDescription" class="form-label">Deskripsi Adegan & Aksi Detail:</label>
                        <textarea id="videoSceneDescription" class="form-control" rows="3" placeholder="Contoh: Seorang astronot berjalan di permukaan Mars, debu merah beterbangan. Di kejauhan, terlihat pangkalan luar angkasa. Matahari terbenam menciptakan bayangan panjang."></textarea>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                     <div class="col-md-6">
                        <label for="videoSetting" class="form-label">Setting/Lokasi Spesifik:</label>
                        <input type="text" id="videoSetting" class="form-control" placeholder="Contoh: hutan ajaib saat fajar, kota cyberpunk di malam hari, pantai tropis saat senja">
                    </div>
                    <div class="col-md-6">
                        <label for="videoCharacterDetails" class="form-label">Detail Karakter/Subjek (Jika Ada):</label>
                        <input type="text" id="videoCharacterDetails" class="form-control" placeholder="Contoh: pria tua bijaksana berjubah, robot AI dengan mata biru menyala">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label for="videoPacing" class="form-label">Kecepatan/Pacing Video:</label>
                        <select id="videoPacing" class="form-select">
                            <option value="">Tidak ditentukan</option>
                            <option value="slow and deliberate">Lambat & Terukur</option>
                            <option value="normal pace">Normal</option>
                            <option value="fast-paced and energetic">Cepat & Enerjik</option>
                            <option value="dynamic with varying speeds">Dinamis (kecepatan bervariasi)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="videoVisualEffects" class="form-label">Efek Visual Khusus:</label>
                        <input type="text" id="videoVisualEffects" class="form-control" placeholder="Contoh: lens flare, motion blur, light trails, bokeh, glitch effect">
                         <small class="form-text text-muted">Pisahkan beberapa efek dengan koma.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="videoTimeOfDay" class="form-label">Waktu dalam Video:</label>
                        <select id="videoTimeOfDay" class="form-select">
                            <option value="">Tidak ditentukan</option>
                            <option value="sunrise / dawn">Matahari Terbit / Fajar</option>
                            <option value="morning">Pagi Hari</option>
                            <option value="midday / noon">Tengah Hari / Siang</option>
                            <option value="afternoon">Sore Hari</option>
                            <option value="golden hour">Golden Hour</option>
                            <option value="sunset / dusk">Matahari Terbenam / Senja</option>
                            <option value="blue hour">Blue Hour</option>
                            <option value="night">Malam Hari</option>
                            <option value="twilight">Twilight</option>
                        </select>
                    </div>
                </div>
                 <div class="row g-3 mt-1">
                    <div class="col-md-12">
                        <label for="videoSoundCues" class="form-label">Petunjuk Suara/Musik (Opsional):</label>
                        <input type="text" id="videoSoundCues" class="form-control" placeholder="Contoh: suara ombak, musik orkestra epik, keheningan, dialog singkat 'Ayo pergi!'">
                        <small class="form-text text-muted">Meskipun AI mungkin tidak menghasilkan suara, ini bisa memengaruhi visual.</small>
                    </div>
                </div>
            </div>

            <button id="generatePromptBtn" class="btn btn-primary btn-lg w-100 mt-4">
                <i class="fas fa-magic me-2"></i>Generate Prompt
            </button>
            <div id="loadingIndicator" class="text-center mt-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Menghubungi server...</p>
            </div>
            <div id="apiErrorMessage" class="alert alert-danger mt-3" style="display: none;"></div>

            <div id="generatedPromptContainer" class="mt-4" style="display:none;">
                <h4 class="mb-3">Prompt yang Dihasilkan:</h4>
                <textarea id="generatedPromptText" class="form-control" rows="8" readonly></textarea>
                <button id="copyPromptBtn" class="btn btn-success mt-2">
                    <i class="fas fa-copy me-2"></i>Salin Prompt
                </button>
                <div id="copyMessage" class="form-text text-success mt-1" style="display: none;">Prompt berhasil disalin!</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const promptTypeSelect = document.getElementById('promptType');
    const outputLanguageSelect = document.getElementById('outputLanguage');
    const imageOptionsContainer = document.getElementById('imageOptionsContainer');
    const videoOptionsContainer = document.getElementById('videoOptionsContainer');
    const generatePromptBtn = document.getElementById('generatePromptBtn');
    const baseIdeaInput = document.getElementById('baseIdea');
    const generatedPromptText = document.getElementById('generatedPromptText');
    const generatedPromptContainer = document.getElementById('generatedPromptContainer');
    const copyPromptBtn = document.getElementById('copyPromptBtn');
    const copyMessage = document.getElementById('copyMessage');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const apiErrorMessage = document.getElementById('apiErrorMessage');

    // Elemen spesifik untuk video
    const videoCameraMovementSelect = document.getElementById('videoCameraMovementSelect');
    const manualCameraMovementContainer = document.getElementById('manualCameraMovementContainer');
    const videoCameraMovementManualInput = document.getElementById('videoCameraMovementManual');

    function toggleOptionsVisibility() {
        if (promptTypeSelect.value === 'image') {
            imageOptionsContainer.classList.add('visible');
            videoOptionsContainer.classList.remove('visible');
        } else { // video
            imageOptionsContainer.classList.remove('visible');
            videoOptionsContainer.classList.add('visible');
        }
    }

    function toggleManualCameraInput() {
        if (videoCameraMovementSelect.value === 'other') {
            manualCameraMovementContainer.classList.add('visible');
        } else {
            manualCameraMovementContainer.classList.remove('visible');
            videoCameraMovementManualInput.value = ''; // Kosongkan input manual jika opsi lain dipilih
        }
    }

    promptTypeSelect.addEventListener('change', toggleOptionsVisibility);
    if (videoCameraMovementSelect) { // Pastikan elemen ada sebelum menambahkan event listener
        videoCameraMovementSelect.addEventListener('change', toggleManualCameraInput);
    }


    generatePromptBtn.addEventListener('click', async function() {
        const baseIdea = baseIdeaInput.value.trim();
        if (!baseIdea) {
            apiErrorMessage.textContent = "Ide dasar prompt tidak boleh kosong.";
            apiErrorMessage.style.display = 'block';
            generatedPromptContainer.style.display = 'none';
            return;
        }

        apiErrorMessage.style.display = 'none';
        generatedPromptContainer.style.display = 'none';
        loadingIndicator.style.display = 'block';
        loadingIndicator.querySelector('p').textContent = 'Menyusun meta-prompt...';

        const selectedLanguage = outputLanguageSelect.value;
        let userChoices = `Subject/Main Idea: "${baseIdea}".\n`;
        userChoices += `Desired Output Language: ${selectedLanguage}.\n`;
        const type = promptTypeSelect.value;
        let modelTarget = "";

        if (type === 'image') {
            userChoices += "Prompt Type: Image.\n";
            userChoices += `Aspect Ratio/Size: ${document.getElementById('imageSize').value}.\n`;
            userChoices += `Visual Style: ${document.getElementById('imageStyle').value}.\n`;
            userChoices += `Artistic Medium: ${document.getElementById('imageMedium').value}.\n`;
            userChoices += `Lighting: ${document.getElementById('imageLighting').value}.\n`;
            userChoices += `Color Scheme: ${document.getElementById('imageColors').value}.\n`;
            modelTarget = "AI image generator";
        } else { // video
            userChoices += "Prompt Type: Video.\n";
            modelTarget = "a high-quality text-to-video AI model like Google Veo";
            userChoices += `Aspect Ratio: ${document.getElementById('videoAspectRatio').value}.\n`;
            // Perkiraan Durasi dihapus
            userChoices += `Visual Style for Video: ${document.getElementById('videoVisualStyle').value}.\n`;
            
            let cameraMovement = videoCameraMovementSelect.value;
            if (cameraMovement === 'other') {
                cameraMovement = videoCameraMovementManualInput.value.trim();
            }
            if (cameraMovement) userChoices += `Camera Movement/Shot Type: ${cameraMovement}.\n`;
            
            const videoMood = document.getElementById('videoMood').value;
            if (videoMood) userChoices += `Mood/Atmosphere: ${videoMood}.\n`;
            
            const videoSceneDescription = document.getElementById('videoSceneDescription').value.trim();
            if (videoSceneDescription) userChoices += `Detailed Scene Description & Action: ${videoSceneDescription}.\n`;
            
            const videoSetting = document.getElementById('videoSetting').value.trim();
            if (videoSetting) userChoices += `Specific Setting/Location: ${videoSetting}.\n`;

            const videoCharacterDetails = document.getElementById('videoCharacterDetails').value.trim();
            if (videoCharacterDetails) userChoices += `Character/Subject Details: ${videoCharacterDetails}.\n`;

            const videoPacing = document.getElementById('videoPacing').value;
            if (videoPacing) userChoices += `Video Pacing: ${videoPacing}.\n`;

            const videoVisualEffects = document.getElementById('videoVisualEffects').value.trim();
            if (videoVisualEffects) userChoices += `Specific Visual Effects: ${videoVisualEffects}.\n`;
            
            const videoTimeOfDay = document.getElementById('videoTimeOfDay').value;
            if (videoTimeOfDay) userChoices += `Time of Day in Video: ${videoTimeOfDay}.\n`;

            const videoSoundCues = document.getElementById('videoSoundCues').value.trim();
            if (videoSoundCues) userChoices += `Sound Cues/Music (optional, for visual influence): ${videoSoundCues}.\n`;
        }

        let metaPromptForGemini = `You are an expert assistant in crafting highly descriptive and creative prompts for ${modelTarget}.
        Generate a detailed and artistic prompt based on the following user-provided information.
        The final prompt must be in ${selectedLanguage}.
        Emphasize rich visual details, artistic direction, and any specified movements or moods.
        Include keywords relevant for high quality (e.g., "highly detailed", "masterpiece", "cinematic quality", "sharp focus", "4K", "8K", "photorealistic", "hyperrealistic" if appropriate for the style and output language).
        If generating a video prompt, consider elements like shot composition, pacing, character actions, environmental details, specific camera angles and movements, and overall narrative flow to create a vivid scene. For Google Veo, it understands natural language for visual effects, camera techniques and can produce photorealistic, high-definition video with consistent characters and natural motion.
        Avoid phrases like "Prompt for AI:" or "Here is your prompt:". Directly provide the prompt text.
        
        User's Information:
        ${userChoices}
        
        Generate the ready-to-use prompt in ${selectedLanguage}.`;

        loadingIndicator.querySelector('p').textContent = 'Menghubungi server ...';

        try {
            const response = await fetch('proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ metaPrompt: metaPromptForGemini })
            });
            
            loadingIndicator.querySelector('p').textContent = 'Memproses respons...';

            if (!response.ok) {
                let errorMsg = `Gagal menghubungi server proxy. Status: ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.error || errorMsg;
                     if(errorData.details && errorData.details.error && errorData.details.error.message) {
                        errorMsg += ` (Detail: ${errorData.details.error.message})`;
                    } else if (errorData.raw_response) {
                        errorMsg += ` (Raw: ${errorData.raw_response.substring(0,100)}...)`;
                    }
                } catch (e) { /* Gagal parse JSON error */ }
                throw new Error(errorMsg);
            }

            const result = await response.json();
            
            if (result.candidates && result.candidates.length > 0 &&
                result.candidates[0].content && result.candidates[0].content.parts &&
                result.candidates[0].content.parts.length > 0) {
                const generatedText = result.candidates[0].content.parts[0].text;
                generatedPromptText.value = generatedText.trim();
                generatedPromptContainer.style.display = 'block';
            } else if (result.error) {
                 throw new Error(`Error dari API Gemini via proxy: ${result.error.message || JSON.stringify(result.error)}`);
            } else {
                console.error("Unexpected API response structure from proxy:", result);
                throw new Error("Gagal mendapatkan teks prompt dari respons server. Struktur respons tidak sesuai.");
            }

        } catch (error) {
            console.error("Error generating prompt via proxy:", error);
            apiErrorMessage.textContent = `Terjadi kesalahan: ${error.message}. Cek konsol untuk detail.`;
            apiErrorMessage.style.display = 'block';
        } finally {
            loadingIndicator.style.display = 'none';
        }
    });

    copyPromptBtn.addEventListener('click', function() {
        generatedPromptText.select();
        generatedPromptText.setSelectionRange(0, 99999); 

        try {
            var successful = document.execCommand('copy');
            copyMessage.textContent = successful ? 'Prompt berhasil disalin!' : 'Gagal menyalin. Salin manual.';
        } catch (err) {
            copyMessage.textContent = 'Gagal menyalin. Salin manual.';
            console.error('Fallback: Oops, unable to copy', err);
        }
        
        copyMessage.style.display = 'block';
        setTimeout(() => { copyMessage.style.display = 'none'; }, 2000);
    });

    // Initialize visibility on page load
    toggleOptionsVisibility();
    if (videoCameraMovementSelect) { // Initialize manual camera input visibility
        toggleManualCameraInput();
    }
});
</script>

<?php include $path_prefix . 'footer.php'; ?>
