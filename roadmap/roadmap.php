<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
        }
        .roadmap {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 60px;
        }
        .container:nth-child(odd) {
            flex-direction: row;
            padding-left:200px;
        }
        .container:nth-child(even) {
            flex-direction: row-reverse;
            padding-right:300px;
        }
        .category {
            padding: 20px 40px;
            background-color: #f4f4f4;
            border-radius: 10px;
            margin-right: 160px;
            font-size: 24px;
            font-weight: bold;
            align-self: center;
        }
        .container:nth-child(even) .category {
            margin-right: 0;
            margin-left: 160px;
        }
        .topics-wrapper {
            display: flex;
        }
        .container:nth-child(odd) .topics-wrapper {
            flex-direction: row-reverse;
        }
        .topics-column {
            display: flex;
            flex-direction: column;
            margin-right: 30px;
        }
        .container:nth-child(even) .topics-column {
            margin-right: 0;
            margin-left: 30px;
        }
        .topic {
            padding: 10px 20px;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-bottom: 15px;
            width: 150px;
            text-align: center;
            font-size: 18px;
        }
        @media screen and (max-width: 768px) {
            .container {
                flex-direction: column;
                margin-bottom: 40px;
            }
            .category {
                margin-right: 0;
                margin-bottom: 30px;
            }
            .container:nth-child(even) .category {
                margin-left: 0;
            }
            .topics-wrapper {
                flex-direction: column;
            }
            .container:nth-child(even) .topics-wrapper {
                flex-direction: column;
            }
            .topics-column {
                margin-right: 0;
            }
            .container:nth-child(even) .topics-column {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Web Development Roadmap</h1>
    <div class="roadmap">
        <div class="container">
            <div class="category">Frontend</div>
            <div class="topics-wrapper">
                <div class="topics-column">
                    <div class="topic">HTML</div>
                    <div class="topic">CSS</div>
                    <div class="topic">JavaScript</div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="category">Backend</div>
            <div class="topics-wrapper">
                <div class="topics-column">
                    <div class="topic">Authentication</div>
                </div>
                <div class="topics-column">
                    <div class="topic">Node.js</div>
                    <div class="topic">Express.js</div>
                    <div class="topic">Databases</div>
                    <div class="topic">APIs</div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="category">Version Control</div>
            <div class="topics-wrapper">
                <div class="topics-column">
                    <div class="topic">Git</div>
                    <div class="topic">GitHub</div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="category">Deployment</div>
            <div class="topics-wrapper">
                <div class="topics-column">
                    <div class="topic">Serverless</div>
                    <div class="topic">CI/CD</div>
                    <div class="topic">Containerization</div>
                    <div class="topic">Cloud Platforms</div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>