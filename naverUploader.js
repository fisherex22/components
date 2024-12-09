const axios = require('axios');
const fs = require('fs').promises;
const FormData = require('form-data');
const path = require('path');

class NaverUploader {
    constructor() {
        this.userId = 'nguyenex';
        this.cookie = 'NAC=cf24CgAyBgugB; NNB=NB6IOSNSQPJGM; _gid=GA1.2.72948258.1730773026; _ga=GA1.1.992977639.1730773026; _ga_EFBDNNF91G=GS1.1.1730773025.1.1.1730773044.0.0.0; NACT=1; nid_inf=4616049; NID_AUT=ejB3M1Drf+e+4YHPUAsRPpe7ZgqDFUsba0CnRzAWIS0P2i1NoSv4dGDiaKa7ZS3C; NID_JKL=8JhokDyIzsx5uYalmVNsTF0r2izXCwqQzx55AMi/Acg=; NID_SES=AAABhdk72+6RIeCV+TV4S+LoAvHDhs65/s2w32VgxWTy3HHpMHtCbsK4x8JtlVAjXRjJQJJt5UMPEkjAlo0O+1e1+jJ7XExsdnNF3cKwPuepTZ09ujK48ElkbnYDtH/wILMRof8mE2aPqrsBZL0Lya+JhmFpz826Rz1oBwbOjA0yZpMKvTiq31nIgGxvrdxIqwKDSrPvkzW4EmM6C4oSkZnuSD6Uinbtz6tFkXeuIAW79zUwGELvmD57pmpwb90Nz5ekVYBTQLjIQ8I9UxJWsbRZhPhmHJyBZnknemyROJAev3VS/YkWLKxkWr7y2qOKKc/aPzIPJ2lKIsNVBKj5LLQmU4Vj4QnonpjJIYv3KZyCV38DSXMzzyPBh2rXHOV3DegVomxrLV8A00cCyr9uGRyljf+MVBnn2WYoO6YypOQyIZYV7Ggz9+G2IRZySTzIZKiGv0ZXvII1xg2okIWDrk5hXMZm7ELUVZav6QeUqn+xfVcMZPCLJ//nfS8+/2qotZM/LEcpQhtFCnVkernW2X/p85I=';
    }

    async getSessionKey(retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await axios({
                    method: 'get',
                    url: 'https://blog.editor.naver.com/PhotoUploader/SessionKey.json',
                    params: {
                        userId: this.userId,
                        serviceId: 'blog',
                        uploaderType: 'simple'
                    },
                    headers: {
                        'Cookie': this.cookie,
                        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                        'Referer': 'https://blog.editor.naver.com/'
                    },
                    timeout: 10000
                });

                if (response.data?.result?.sessionKey) {
                    return {
                        success: true,
                        sessionKey: response.data.result.sessionKey
                    };
                }
            } catch (error) {
                console.error(`Attempt ${i + 1} failed:`, error.message);
                if (i < retries - 1) await new Promise(r => setTimeout(r, 2000));
            }
        }
        return { success: false, error: 'Failed to get session key' };
    }

    async uploadImage(imagePath, sessionKey, index = 0) {
        try {
            // Verify file exists and is readable
            try {
                await fs.access(imagePath, fs.constants.R_OK);
            } catch (error) {
                throw new Error(`Cannot access file ${imagePath}: ${error.message}`);
            }

            const form = new FormData();
            const fileStream = await fs.readFile(imagePath);
            form.append('image', fileStream, {
                filename: path.basename(imagePath),
                contentType: 'image/jpeg'
            });

            const response = await axios({
                method: 'post',
                url: `https://blog.upphoto.naver.com/${sessionKey}/simpleUpload/${index}`,
                params: {
                    userId: this.userId,
                    extractExif: false,
                    extractAnimatedCnt: true,
                    autorotate: true,
                    extractDominantColor: true,
                    type: 'autocrop'
                },
                headers: {
                    ...form.getHeaders(),
                    'Cookie': this.cookie,
                    'Referer': 'https://blog.editor.naver.com/'
                },
                data: form,
                maxContentLength: Infinity,
                maxBodyLength: Infinity,
                timeout: 30000
            });

            const urlMatch = response.data.match(/<url>([^<]+)<\/url>/);
            if (urlMatch) {
                // Delete the local file after successful upload
                await fs.unlink(imagePath);
                console.log(`[SUCCESS] Deleted local file: ${imagePath}`);
            
                return {
                    success: true,
                    url: 'https://blogfiles.pstatic.net' + urlMatch[1]
                };
            }

            throw new Error('Could not extract URL from response');
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }

    async uploadMemoImages(imagePaths) {
        if (!Array.isArray(imagePaths)) {
            imagePaths = [imagePaths];
        }

        // Process one image per request
        const results = [];
        for (let imagePath of imagePaths) {
            const form = new FormData();
            const fileStream = await fs.readFile(imagePath);
            form.append('uploadPhoto', fileStream, {
                filename: path.basename(imagePath),
                contentType: 'image/jpeg'
            });

            try {
                const response = await axios({
                    method: 'post',
                    url: 'https://memo.naver.com/memo/uploadPhotoJson',
                    headers: {
                        ...form.getHeaders(),
                        'authority': 'memo.naver.com',
                        'accept': '*/*',
                        'cookie': 'NAC=cf24CgAyBgugB; NNB=NB6IOSNSQPJGM; _ga=GA1.1.992977639.1730773026; _ga_EFBDNNF91G=GS1.1.1730773025.1.1.1730773044.0.0.0; nid_inf=4616049; NID_AUT=ejB3M1Drf+e+4YHPUAsRPpe7ZgqDFUsba0CnRzAWIS0P2i1NoSv4dGDiaKa7ZS3C; NID_JKL=8JhokDyIzsx5uYalmVNsTF0r2izXCwqQzx55AMi/Acg=; NID_SES=AAABhLoWJ4hGIlPaeY2PusSFI0s7FMY9GCbf4ZfIJyPEki/B0vaeusZbJNNpi8rXPkUFvyoxaIytonQWt60s/d5as1YRhaliDR9/kAFQf3zlOa326vdv2sl+GgauxKmnbq8u+TSTOq021u37aQuMKeXj1wxJdw/ynZybHdvm1i1gV/h3jX6qGx50p2hft4XBs5Ve62vQKzd8YFfjAPzSem8evNMSDFv6eWxnbIX9C6NyhZ+toWRDo9JpAGinEW83J8py/qZDbHYnyYJwPI1nyjs/0tB9fKISo7AY7qdW7cK8bjkXX+/v8NhtLZp5IkHHSa/32zLjNpu+rPpaTRwSv+E7ByR5huuO5gVMGUL/0FJTUo0kYUXr7SwQY9f4TS/8zuQyVZY+iRGYCqz5wQw62o0mR+Uvtsw/wnw7UAhPkElWdMbM9CSB+n31OfUh582mnq1sFOocsO/Qz0rIXo8nWz3hH8X/9AhBu/zJhlWSzh+HM1gcHB5mrvjMWLF7RPel9dr0VtOBT8bCzBzTZLGkAijV+j0=',
                        'origin': 'https://memo.naver.com',
                        'referer': 'https://memo.naver.com/'
                    },
                    data: form
                });

                if (response.data.code === 'SUCCESS') {
                    const url = 'https://phinf.pstatic.net/memo' + response.data.photoUrl + '?type=w740';
                    results.push(url);
                    // Delete the local file after successful upload
                    await fs.unlink(imagePath);
                    console.log('[SUCCESS] Uploaded and deleted local file:', imagePath);
                }
            } catch (error) {
                console.log('[ERROR] Failed to upload:', imagePath, error.message);
            }
        }

        return {
            success: results.length > 0,
            urls: results
        };
    }}

    async function showHelp() {
        console.log(`
    Naver Image Uploader - Usage:
    
    node naverUploader.js <command> [arguments]

    Commands:
    getSessionKey
        Gets a new session key from Naver
        Example: node naverUploader.js getSessionKey

    uploadWithSession <imagePath> <sessionKey> [index]
        Uploads an image using a session key
        Example: node naverUploader.js uploadWithSession ./image.jpg SESSION_KEY_HERE 0

    uploadMemo <imagePath1> [imagePath2] ... [imagePath10]
        Uploads up to 10 images to Naver Memo
        Example: node naverUploader.js uploadMemo ./image1.jpg ./image2.jpg

    test
        Runs test upload with sample image
        Example: node naverUploader.js test

    help
        Shows this help message
        Example: node naverUploader.js help
        `);
    }

async function main() {
    const command = process.argv[2];
    const uploader = new NaverUploader();

    if (!command || command === 'help') {
        await showHelp();
        return;
    }

    try {
        switch (command) {
            case 'getSessionKey':
                const sessionResult = await uploader.getSessionKey();
                console.log(JSON.stringify(sessionResult, null, 2));
                process.exit(sessionResult.success ? 0 : 1);
                break;

            case 'uploadWithSession': {
                const imagePath = process.argv[3];
                const sessionKey = process.argv[4];
                const index = parseInt(process.argv[5] || '0');

                if (!imagePath || !sessionKey) {
                    throw new Error('Missing required parameters. Use: uploadWithSession <imagePath> <sessionKey> [index]');
                }

                const uploadResult = await uploader.uploadImage(imagePath, sessionKey, index);
                console.log(JSON.stringify(uploadResult, null, 2));
                process.exit(uploadResult.success ? 0 : 1);
                break;
            }

            case 'uploadMemo': {
                const imagePaths = process.argv.slice(3);
                if (imagePaths.length === 0) {
                    throw new Error('Missing required parameters. Use: uploadMemo <imagePath1> [imagePath2] ... [imagePath10]');
                }
                if (imagePaths.length > 10) {
                    throw new Error('Maximum 10 images can be uploaded at once');
                }
                const uploadResult = await uploader.uploadMemoImages(imagePaths);
                console.log(JSON.stringify(uploadResult, null, 2));
                process.exit(uploadResult.success ? 0 : 1);
                break;
            }

            case 'test': {
                console.log('Running test upload...');
                const sessionResult = await uploader.getSessionKey();
                if (!sessionResult.success) {
                    throw new Error('Failed to get session key: ' + sessionResult.error);
                }

                const testImagePath = path.join(__dirname, 'test.jpg');
                console.log(`Attempting to upload test image: ${testImagePath}`);
                
                if (!fs.existsSync(testImagePath)) {
                    throw new Error('Test image not found. Please create test.jpg in the same directory.');
                }

                const uploadResult = await uploader.uploadImage(testImagePath, sessionResult.sessionKey);
                console.log('Upload result:', JSON.stringify(uploadResult, null, 2));
                process.exit(uploadResult.success ? 0 : 1);
                break;
            }

            default:
                throw new Error(`Unknown command: ${command}`);
        }
    } catch (error) {
        console.error('Error:', error.message);
        console.log(JSON.stringify({
            success: false,
            error: error.message
        }));
        process.exit(1);
    }
}

// Run if called directly
if (require.main === module) {
    main().catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}

module.exports = NaverUploader;