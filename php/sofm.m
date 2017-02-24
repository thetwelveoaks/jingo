in_dir = 'input/';
clusout_dir = 'clus_output/';
distout_dir = 'dist_output/';

f_start = 2;
f_end = 500; %inclusive
f_ext = '.csv';
itvl = 30; %minutes

for f = f_start:f_end
    dataset = csvread(strcat(in_dir, num2str(f), f_ext));
    dataset = dataset';
    net = selforgmap([10 10]);
    net.trainParam.epochs = 200;
    [net, tr] = train(net, dataset);
    y = net(dataset);
    
    classes = vec2ind(y);
    weights = net.IW{1,1};
    
    [m, n] = size(classes);
    for i = 1:n
        dataset(2, i) = weights(classes(1, i), 2);
    end
    
    dataset = dataset';
    csvwrite(strcat(clusout_dir, num2str(f), f_ext), dataset);
    
    [ret, indexes] = sort(dataset(:, 1));
    dataset = dataset(indexes, :);
    prev = 1;
    curr = prev;
    t = itvl;
    dist_res = zeros([1 3]);
    dist_row = 1;
    
    while curr <= n
        while curr <= n && dataset(curr, 1) < t
            curr = curr + 1;
        end
        sample = dataset(prev : curr - 1, 2);
        try
            pd = fitdist(sample, 'Weibull');
        catch ME
            if(strcmp(ME.identifier, 'stats:ProbDistUnivParam:fit:InsufficientData'))
                warning(strcat('Fitting has problems at f=', num2str(f), ...
                     ' curr=', num2str(curr), ' t=', num2str(t)));
                t  = t + itvl;
                continue;
            else
                rethrow(ME);
            end
        end
        dist_res(dist_row, :) = [t, pd.A, pd.B];
     
        dist_row = dist_row + 1;
        prev = curr;
        t  = t + itvl;
    end

    csvwrite(strcat(distout_dir, num2str(f), f_ext), dist_res);
end




